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

if (!isset($data->requester_name) || !isset($data->department) || !isset($data->material_id) || !isset($data->quantity)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Check: Ensure the material actually exists and fetch its name
    $stmtCheck = $pdo2->prepare("SELECT id, name FROM mt_materials WHERE id = :id");
    $stmtCheck->execute([':id' => $data->material_id]);
    $materialInfo = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$materialInfo) {
        echo json_encode(['success' => false, 'message' => 'Material not found']);
        exit;
    }
    
    $materialName = $materialInfo['name'];

    $sql = "INSERT INTO mt_requests (request_date, requester_name, department, material_id, quantity, status) 
            VALUES (CURDATE(), :requester_name, :department, :material_id, :quantity, 'pending')";

    $stmt = $pdo2->prepare($sql);

    $stmt->execute([
        ':requester_name' => $data->requester_name,
        ':department' => $data->department,
        ':material_id' => $data->material_id,
        ':quantity' => $data->quantity
    ]);

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
                                "text" => "📦 คำขอเบิกวัสดุใหม่",
                                "weight" => "bold",
                                "size" => "lg",
                                "color" => "#ffffff",
                                "align" => "center"
                            ]
                        ]
                    ],
                    "body" => [
                        "type" => "box",
                        "layout" => "vertical",
                        "contents" => [
                            [
                                "type" => "text",
                                "text" => $materialName,
                                "weight" => "bold",
                                "size" => "lg",
                                "color" => "#0d6efd",
                                "align" => "center",
                                "wrap" => true,
                                "margin" => "md"
                            ],
                            [
                                "type" => "separator",
                                "margin" => "md"
                            ],
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
                                            ["type" => "text", "text" => $data->requester_name, "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 4]
                                        ]
                                    ],
                                    [
                                        "type" => "box",
                                        "layout" => "baseline",
                                        "spacing" => "sm",
                                        "contents" => [
                                            ["type" => "text", "text" => "หน่วยงาน", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                            ["type" => "text", "text" => $data->department, "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 4]
                                        ]
                                    ],
                                    [
                                        "type" => "box",
                                        "layout" => "baseline",
                                        "spacing" => "sm",
                                        "contents" => [
                                            ["type" => "text", "text" => "จำนวน", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                            ["type" => "text", "text" => (string)$data->quantity, "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 4]
                                        ]
                                    ]
                                ]
                            ]
                        ]
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
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
