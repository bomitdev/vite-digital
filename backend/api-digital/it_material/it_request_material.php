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
            SELECT p.ID, TRIM(ds.HR_DEPARTMENT_SUB_NAME) as dept_sub, TRIM(d.HR_DEPARTMENT_NAME) as dept_main 
            FROM hr_person p 
            LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID 
            LEFT JOIN hr_department_sub ds ON p.HR_DEPARTMENT_SUB_ID = ds.HR_DEPARTMENT_SUB_ID
            LEFT JOIN hr_department d ON p.HR_DEPARTMENT_ID = d.HR_DEPARTMENT_ID
            WHERE p.ID = :pid
               OR CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) = :name1
               OR CONCAT(p.HR_FNAME, ' ', p.HR_LNAME) = :name2
               OR REPLACE(CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME), ' ', '') = REPLACE(:name3, ' ', '')
               OR :name4 LIKE CONCAT('%', p.HR_FNAME, '%', p.HR_LNAME, '%')
            LIMIT 1
        ");
        $stmtP->execute([
            ':pid' => $requester_id ?: 0,
            ':name1' => $cleanName,
            ':name2' => $cleanName,
            ':name3' => $cleanName,
            ':name4' => $cleanName
        ]);
        $personFound = $stmtP->fetch(PDO::FETCH_ASSOC);
        if ($personFound) {
            $requester_id = intval($personFound['ID']);
            $hrDept = !empty($personFound['dept_sub']) ? $personFound['dept_sub'] : $personFound['dept_main'];
            if (!empty($hrDept) && (empty($data->department) || trim($data->department) === 'กลุ่มงานบริหารทั่วไป')) {
                $data->department = $hrDept;
            }
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
        // กลุ่มงานสารสนเทศและศูนย์คอมพิวเตอร์ (IT Department) - ดึงจากไฟล์ .env
        $client_key = $_ENV['MOPH_IT_CLIENT_KEY'] ?? getenv('MOPH_IT_CLIENT_KEY') ?: '151518fbb68266eb98604e010d4ffdb231365f32';
        $secret_key = $_ENV['MOPH_IT_SECRET_KEY'] ?? getenv('MOPH_IT_SECRET_KEY') ?: 'KVQRE5QY6PEDRAQRX6UGQU7QOO5Y';
        $url        = $_ENV['MOPH_NOTIFY_URL']    ?? getenv('MOPH_NOTIFY_URL')    ?: 'https://morpromt2f.moph.go.th/api/notify/send';

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
                                ],
                                [
                                    "type" => "text",
                                    "text" => "งานสารสนเทศและศูนย์คอมพิวเตอร์",
                                    "size" => "xs",
                                    "color" => "#cfe2ff",
                                    "align" => "center",
                                    "margin" => "xs"
                                ]
                            ]
                        ],
                        "body" => [
                            "type" => "box",
                            "layout" => "vertical",
                            "contents" => array_merge([
                                [
                                    "type" => "box",
                                    "layout" => "vertical",
                                    "margin" => "md",
                                    "spacing" => "sm",
                                    "contents" => [
                                        [
                                            "type" => "box",
                                            "layout" => "baseline",
                                            "spacing" => "sm",
                                            "contents" => [
                                                ["type" => "text", "text" => "เลขที่คำขอ", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                                ["type" => "text", "text" => $request_no, "wrap" => true, "color" => "#0d6efd", "size" => "sm", "weight" => "bold", "flex" => 5]
                                            ]
                                        ],
                                        [
                                            "type" => "box",
                                            "layout" => "baseline",
                                            "spacing" => "sm",
                                            "contents" => [
                                                ["type" => "text", "text" => "ผู้ขอเบิก", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                                ["type" => "text", "text" => (string)$data->requester_name, "wrap" => true, "color" => "#333333", "size" => "sm", "weight" => "bold", "flex" => 5]
                                            ]
                                        ],
                                        [
                                            "type" => "box",
                                            "layout" => "baseline",
                                            "spacing" => "sm",
                                            "contents" => [
                                                ["type" => "text", "text" => "หน่วยงาน", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                                ["type" => "text", "text" => (string)$data->department, "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 5]
                                            ]
                                        ],
                                        [
                                            "type" => "box",
                                            "layout" => "baseline",
                                            "spacing" => "sm",
                                            "contents" => [
                                                ["type" => "text", "text" => "วันที่ขอเบิก", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                                ["type" => "text", "text" => date('d/m/Y H:i น.'), "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 5]
                                            ]
                                        ]
                                    ]
                                ],
                                [
                                    "type" => "separator",
                                    "margin" => "md"
                                ],
                                [
                                    "type" => "text",
                                    "text" => "รายการที่ขอเบิก",
                                    "weight" => "bold",
                                    "size" => "sm",
                                    "color" => "#0d6efd",
                                    "margin" => "md"
                                ]
                            ], $lineItems)
                        ]
                    ]
                ]
            ]
        ];

        $headers = [
            "Content-Type: application/json",
            "client-key: {$client_key}",
            "secret-key: {$secret_key}"
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($linePayload, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        error_log("it_request_material Notify Result: HTTP $httpCode, Res: $res, Err: $curlErr, URL: $url, ClientKey: " . substr($client_key, 0, 8) . "...");
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
