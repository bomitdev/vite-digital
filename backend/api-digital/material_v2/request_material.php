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
    // Check if request_no exists, if not create it
    $checkColumn = $pdo2->query("SHOW COLUMNS FROM mt_requests LIKE 'request_no'")->fetch();
    if (!$checkColumn) {
        $pdo2->exec("ALTER TABLE mt_requests ADD COLUMN request_no VARCHAR(50) AFTER id");
    }

    $pdo2->beginTransaction();

    $request_no = 'REQ-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));

    $stmtCheck = $pdo2->prepare("SELECT id, name FROM mt_materials WHERE id = :id");
    $sql = "INSERT INTO mt_requests (request_date, requester_name, department, material_id, quantity, status, request_no) 
            VALUES (CURDATE(), :requester_name, :department, :material_id, :quantity, 'pending', :request_no)";
    $stmt = $pdo2->prepare($sql);

    $materialNames = [];
    $lineItems = [];

    foreach ($items as $item) {
        $stmtCheck->execute([':id' => $item->material_id]);
        $materialInfo = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$materialInfo) {
            throw new Exception("Material not found");
        }

        $stmt->execute([
            ':requester_name' => $data->requester_name,
            ':department' => $data->department,
            ':material_id' => $item->material_id,
            ':quantity' => $item->quantity,
            ':request_no' => $request_no
        ]);

        $materialNames[] = $materialInfo['name'];
        $lineItems[] = [
            "type" => "box",
            "layout" => "baseline",
            "spacing" => "sm",
            "contents" => [
                ["type" => "text", "text" => "- " . $materialInfo['name'], "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 4],
                ["type" => "text", "text" => (string)$item->quantity, "color" => "#0d6efd", "size" => "sm", "flex" => 1, "align" => "end", "weight" => "bold"]
            ]
        ];
    }

    $pdo2->commit();

    // --- LINE NOTIFY START ---
    $client_key = '151518fbb68266eb98604e010d4ffdb231365f32';
    $secret_key = 'KVQRE5QY6PEDRAQRX6UGQU7QOO5Y';

    $linePayload = [
        "messages" => [
            [
                "type" => "flex",
                "altText" => "คำขอเบิกวัสดุคอมพิวเตอร์ใหม่",
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
                                "text" => "📦 คำขอเบิกวัสดุใหม่ (" . count($items) . " รายการ)",
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
                                            ["type" => "text", "text" => "ผู้เบิก", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                            ["type" => "text", "text" => $data->requester_name, "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 5]
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
                                    ]
                                ]
                            ],
                            [
                                "type" => "separator",
                                "margin" => "md"
                            ],
                            [
                                "type" => "text",
                                "text" => "รายการที่เบิก",
                                "weight" => "bold",
                                "size" => "sm",
                                "color" => "#aaaaaa",
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

    curl_exec($ch);
    // --- LINE NOTIFY END ---

    echo json_encode([
        'success' => true,
        'message' => 'Material request submitted successfully'
    ]);
} catch (Exception $e) {
    if (isset($pdo2) && $pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
