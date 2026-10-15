<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';

// Secure Auth
$userData = authGuard();

if (!isset($pdo2)) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->requester_name) || !isset($data->department)) {
    echo json_encode(['success' => false, 'message' => 'Missing requester info']);
    exit;
}

$items = [];
if (isset($data->items) && is_array($data->items) && count($data->items) > 0) {
    $items = $data->items;
} elseif (isset($data->material_id) && isset($data->quantity)) {
    $items[] = (object)[
        'material_id' => $data->material_id,
        'quantity' => $data->quantity
    ];
} else {
    echo json_encode(['success' => false, 'message' => 'Missing items']);
    exit;
}

try {
    // 1. Resolve requester_id
    $requester_id = null;
    if (isset($data->requester_id) && intval($data->requester_id) > 0) {
        $requester_id = intval($data->requester_id);
    } elseif (isset($pdo3) && !empty($data->requester_name)) {
        $cleanName = trim($data->requester_name);
        $stmtP = $pdo3->prepare("
            SELECT p.ID 
            FROM hr_person p 
            LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID 
            WHERE CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) = :name1
               OR CONCAT(p.HR_FNAME, ' ', p.HR_LNAME) = :name2
               OR REPLACE(CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME), ' ', '') = REPLACE(:name3, ' ', '')
               OR :name4 LIKE CONCAT('%', p.HR_FNAME, '%', p.HR_LNAME, '%')
            LIMIT 1
        ");
        $stmtP->execute([
            ':name1' => $cleanName,
            ':name2' => $cleanName,
            ':name3' => $cleanName,
            ':name4' => $cleanName
        ]);
        $personFound = $stmtP->fetch(PDO::FETCH_ASSOC);
        if ($personFound) {
            $requester_id = intval($personFound['ID']);
        } elseif (isset($userData['uid'])) {
            $requester_id = intval($userData['uid']);
        }
    } elseif (!$requester_id && isset($userData['uid'])) {
        $requester_id = intval($userData['uid']);
    }

    $pdo2->beginTransaction();

    $request_no = 'REQ-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));

    $stmtCheck = $pdo2->prepare("SELECT id, name, unit, balance FROM mt_it_materials WHERE id = :id");

    $sqlInsert = "INSERT INTO mt_it_requests (request_date, requester_name, requester_id, department, material_id, quantity, status, request_no) 
            VALUES (CURDATE(), :requester_name, :requester_id, :department, :material_id, :quantity, 'pending', :request_no)";
    $stmtInsert = $pdo2->prepare($sqlInsert);

    $lineItems = [];

    foreach ($items as $item) {
        if (!isset($item->material_id) || !isset($item->quantity)) {
            throw new Exception("Invalid item format missing material_id or quantity");
        }

        $stmtCheck->execute([':id' => $item->material_id]);
        $materialRow = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$materialRow) {
            throw new Exception("Material ID {$item->material_id} not found");
        }

        if ($item->quantity <= 0) {
            throw new Exception("Quantity must be greater than 0");
        }

        if ($item->quantity > $materialRow['balance']) {
            throw new Exception("Insufficient stock balance for material ID {$item->material_id}. Requested: {$item->quantity}, Available: {$materialRow['balance']}");
        }

        $stmtInsert->execute([
            ':requester_name' => $data->requester_name,
            ':requester_id' => $requester_id,
            ':department' => trim($data->department),
            ':material_id' => $item->material_id,
            ':quantity' => $item->quantity,
            ':request_no' => $request_no
        ]);

        $unitStr = !empty($materialRow['unit']) ? ' ' . $materialRow['unit'] : '';
        $lineItems[] = [
            "type" => "box",
            "layout" => "baseline",
            "spacing" => "sm",
            "contents" => [
                ["type" => "text", "text" => "- " . $materialRow['name'], "wrap" => true, "color" => "#555555", "size" => "sm", "flex" => 4],
                ["type" => "text", "text" => (string)$item->quantity . $unitStr, "color" => "#0d6efd", "size" => "sm", "flex" => 2, "align" => "end", "weight" => "bold"]
            ]
        ];
    }

    $pdo2->commit();

    // --- MOPH NOTIFY START ---
    try {
        $client_key = 'a883bc9bb2ff70b96fbd1285e581aa2edd7e81bc';
        $secret_key = '52ZILLQKT4UZCQVZHMTCQGYQHPSA';

        $linePayload = [
            "messages" => [
                [
                    "type" => "flex",
                    "altText" => "คำขอเบิกวัสดุคอมพิวเตอร์ใหม่ (เลขที่: " . $request_no . ")",
                    "contents" => [
                        "type" => "bubble",
                        "direction" => "ltr",
                        "header" => [
                            "type" => "box",
                            "layout" => "vertical",
                            "backgroundColor" => "#0d6efd",
                            "contents" => [
                                [
                                    "type" => "text",
                                    "text" => "💻 คำขอเบิกวัสดุคอมพิวเตอร์ใหม่ (" . count($items) . " รายการ)",
                                    "weight" => "bold",
                                    "size" => "md",
                                    "color" => "#ffffff",
                                    "align" => "center"
                                ]
                            ]
                        ],
                        "body" => [
                            "type" => "box",
                            "layout" => "vertical",
                            "spacing" => "sm",
                            "contents" => [
                                [
                                    "type" => "box",
                                    "layout" => "horizontal",
                                    "contents" => [
                                        ["type" => "text", "text" => "ผู้ขอเบิก:", "color" => "#888888", "size" => "xs", "flex" => 2],
                                        ["type" => "text", "text" => (string)$data->requester_name, "weight" => "bold", "color" => "#333333", "size" => "xs", "flex" => 4]
                                    ]
                                ],
                                [
                                    "type" => "box",
                                    "layout" => "horizontal",
                                    "contents" => [
                                        ["type" => "text", "text" => "หน่วยงาน:", "color" => "#888888", "size" => "xs", "flex" => 2],
                                        ["type" => "text", "text" => (string)$data->department, "weight" => "bold", "color" => "#333333", "size" => "xs", "flex" => 4]
                                    ]
                                ],
                                [
                                    "type" => "box",
                                    "layout" => "horizontal",
                                    "contents" => [
                                        ["type" => "text", "text" => "เลขที่คำขอ:", "color" => "#888888", "size" => "xs", "flex" => 2],
                                        ["type" => "text", "text" => $request_no, "color" => "#555555", "size" => "xs", "flex" => 4]
                                    ]
                                ],
                                [
                                    "type" => "separator",
                                    "margin" => "md"
                                ],
                                [
                                    "type" => "text",
                                    "text" => "รายการวัสดุ:",
                                    "weight" => "bold",
                                    "size" => "xs",
                                    "color" => "#333333",
                                    "margin" => "md"
                                ],
                                [
                                    "type" => "box",
                                    "layout" => "vertical",
                                    "margin" => "sm",
                                    "spacing" => "xs",
                                    "contents" => $lineItems
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $url = 'https://notify-api.moph.go.th/api/v1/notify';
        $headers = [
            'Content-Type: application/json',
            'client-key: ' . $client_key,
            'secret-key: ' . $secret_key
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($linePayload, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_exec($ch);
        unset($ch);
    } catch (Exception $notifyEx) {
        error_log("MOPH Notify Error in it_request_material: " . $notifyEx->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'Material request submitted successfully'
    ]);
} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
