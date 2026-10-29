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

if (!isset($data->request_no) || empty($data->request_no)) {
    echo json_encode(['success' => false, 'message' => 'Missing request_no']);
    exit;
}

if (!isset($data->requester_name) || !isset($data->department)) {
    echo json_encode(['success' => false, 'message' => 'Missing requester info']);
    exit;
}

$items = [];
if (isset($data->items) && is_array($data->items)) {
    $items = $data->items;
}

if (empty($items)) {
    echo json_encode(['success' => false, 'message' => 'No items provided']);
    exit;
}

try {
    $pdo2->beginTransaction();

    // Check if the request is still pending
    $stmtCheckStatus = $pdo2->prepare("SELECT * FROM mt_admin_requests WHERE request_no = :request_no LIMIT 1");
    $stmtCheckStatus->execute([':request_no' => $data->request_no]);
    $requestRow = $stmtCheckStatus->fetch(PDO::FETCH_ASSOC);

    if (!$requestRow) {
        throw new Exception("ไม่พบคำขอเบิกที่ระบุ");
    }

    if ($requestRow['status'] !== 'pending') {
        throw new Exception("ไม่สามารถแก้ไขได้เนื่องจากคำขอนี้ได้รับการประมวลผลแล้ว (สถานะ: " . $requestRow['status'] . ")");
    }

    // Ownership check: แก้ได้เฉพาะของตัวเองเท่านั้น ใครเบิกคนนั้นสามารถแก้ไขได้
    $isOwner = false;
    $currentUid = isset($userData['uid']) ? intval($userData['uid']) : 0;

    if ($currentUid > 0 && !empty($requestRow['requester_id']) && intval($requestRow['requester_id']) === $currentUid) {
        $isOwner = true;
    }

    if (!$isOwner) {
        $currentName = trim($userData['name'] ?? '');
        $existingName = trim($requestRow['requester_name'] ?? '');

        if (!empty($currentName) && !empty($existingName)) {
            $cleanCurrent = preg_replace('/^(นาย|นางสาว|นาง|ดร\.|นพ\.|พญ\.|ทพ\.|ทพญ\.|ภก\.|ภญ\.|ว่าที่\s*ร\.ต\.|ร\.ต\.|อ\.)\s*/u', '', $currentName);
            $cleanCurrent = preg_replace('/\s+/', '', $cleanCurrent);

            $cleanExisting = preg_replace('/^(นาย|นางสาว|นาง|ดร\.|นพ\.|พญ\.|ทพ\.|ทพญ\.|ภก\.|ภญ\.|ว่าที่\s*ร\.ต\.|ร\.ต\.|อ\.)\s*/u', '', $existingName);
            $cleanExisting = preg_replace('/\s+/', '', $cleanExisting);

            if ($cleanCurrent === $cleanExisting || $currentName === $existingName) {
                $isOwner = true;
            }
        }
    }

    if (!$isOwner && isset($pdo3) && $currentUid > 0) {
        $stmtHr = $pdo3->prepare("
            SELECT CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as fullname,
                   CONCAT(p.HR_FNAME, ' ', p.HR_LNAME) as name_no_prefix
            FROM hr_person p
            LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
            WHERE p.ID = :id
        ");
        $stmtHr->execute([':id' => $currentUid]);
        $hrUser = $stmtHr->fetch(PDO::FETCH_ASSOC);
        if ($hrUser) {
            $existingName = trim($requestRow['requester_name'] ?? '');
            if ($existingName === trim($hrUser['fullname']) || $existingName === trim($hrUser['name_no_prefix'])) {
                $isOwner = true;
            }
        }
    }

    if (!$isOwner) {
        $pdo2->rollBack();
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'message' => 'คุณสามารถแก้ไขได้เฉพาะคำขอเบิกของตัวเองเท่านั้น'
        ]);
        exit;
    }

    // Migration: Add requester_position if missing
    $checkColPos = $pdo2->query("SHOW COLUMNS FROM mt_admin_requests LIKE 'requester_position'")->fetch();
    if (!$checkColPos) {
        $pdo2->exec("ALTER TABLE mt_admin_requests ADD COLUMN requester_position VARCHAR(255) NULL AFTER requester_id");
    }

    // Resolve requester_id and requester_position
    $requester_id = isset($data->requester_id) && !empty($data->requester_id) ? intval($data->requester_id) : null;
    $requester_position = isset($data->requester_position) && !empty($data->requester_position) ? trim($data->requester_position) : '';

    if (isset($pdo3) && !empty($data->requester_name)) {
        $cleanName = trim($data->requester_name);
        $stmtP = $pdo3->prepare("
            SELECT p.ID,
                   CONCAT(IFNULL(pos.HR_POSITION_NAME, ''), IF(hl.HR_LEVEL_NAME IS NOT NULL AND hl.HR_LEVEL_NAME != '', CONCAT(' ', hl.HR_LEVEL_NAME), '')) as position_name
            FROM hr_person p 
            LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID 
            LEFT JOIN hr_position pos ON p.HR_POSITION_ID = pos.HR_POSITION_ID
            LEFT JOIN hr_level hl ON p.HR_LEVEL_ID = hl.HR_LEVEL_ID
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
            if (empty($requester_position) && !empty($personFound['position_name'])) {
                $requester_position = $personFound['position_name'];
            }
        }
    }

    // Delete old items
    $stmtDelete = $pdo2->prepare("DELETE FROM mt_admin_requests WHERE request_no = :request_no");
    $stmtDelete->execute([':request_no' => $data->request_no]);

    $stmtCheck = $pdo2->prepare("SELECT id, name, unit, balance FROM mt_admin_materials WHERE id = :id");

    $sqlInsert = "INSERT INTO mt_admin_requests (request_date, requester_name, requester_id, requester_position, department, material_id, quantity, status, request_no) 
            VALUES (CURDATE(), :requester_name, :requester_id, :requester_position, :department, :material_id, :quantity, 'pending', :request_no)";
    $stmtInsert = $pdo2->prepare($sqlInsert);

    $lineItems = [];

    foreach ($items as $item) {
        if (!isset($item->material_id) || !isset($item->quantity)) {
            throw new Exception("Invalid item format missing material_id or quantity");
        }

        // Verify material exists and has enough balance
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
            ':requester_position' => $requester_position,
            ':department' => trim($data->department),
            ':material_id' => $item->material_id,
            ':quantity' => $item->quantity,
            ':request_no' => $data->request_no
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
                    "altText" => "แก้ไขคำขอเบิกวัสดุงานบริหารทั่วไป (เลขที่: " . $data->request_no . ")",
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
                                    "text" => "📝 แก้ไขคำขอเบิกวัสดุ (" . count($items) . " รายการ)",
                                    "weight" => "bold",
                                    "size" => "md",
                                    "color" => "#ffffff",
                                    "align" => "center"
                                ],
                                [
                                    "type" => "text",
                                    "text" => "งานบริหารทั่วไปและธุรการ",
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
                                                ["type" => "text", "text" => $data->request_no, "wrap" => true, "color" => "#0d6efd", "size" => "sm", "weight" => "bold", "flex" => 5]
                                            ]
                                        ],
                                        [
                                            "type" => "box",
                                            "layout" => "baseline",
                                            "spacing" => "sm",
                                            "contents" => [
                                                ["type" => "text", "text" => "ผู้เบิก", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                                ["type" => "text", "text" => $data->requester_name, "wrap" => true, "color" => "#333333", "size" => "sm", "weight" => "bold", "flex" => 5]
                                            ]
                                        ],
                                        [
                                            "type" => "box",
                                            "layout" => "baseline",
                                            "spacing" => "sm",
                                            "contents" => [
                                                ["type" => "text", "text" => "หน่วยงาน", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                                ["type" => "text", "text" => $data->department, "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 5]
                                            ]
                                        ],
                                        [
                                            "type" => "box",
                                            "layout" => "baseline",
                                            "spacing" => "sm",
                                            "contents" => [
                                                ["type" => "text", "text" => "วันที่แก้ไข", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
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
                                    "text" => "รายการที่ขอเบิกใหม่",
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

        $url = "https://morpromt2f.moph.go.th/api/notify/send";
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_exec($ch);
        unset($ch);
    } catch (Exception $notifyEx) {
        error_log("MOPH Notify Error in admin_update_request: " . $notifyEx->getMessage());
    }
    // --- MOPH NOTIFY END ---

    echo json_encode([
        'success' => true,
        'message' => 'Material request updated successfully'
    ]);
} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
