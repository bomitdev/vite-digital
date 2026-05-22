<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (
    !isset($data->asset_id) || empty($data->asset_id) ||
    !isset($data->borrower_name) || empty(trim($data->borrower_name)) ||
    !isset($data->department) || empty(trim($data->department)) ||
    !isset($data->expected_return_date) || empty(trim($data->expected_return_date)) ||
    !isset($data->objective) || empty(trim($data->objective))
) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $borrowDate = date('Y-m-d H:i:s');

    $stmt = $pdo2->prepare("INSERT INTO it_loans 
        (asset_id, borrower_name, department, objective, borrow_date, expected_return_date, status) 
        VALUES (:asset_id, :borrower_name, :department, :objective, :borrow_date, :expected_return_date, 'pending')");

    $stmt->execute([
        ':asset_id' => $data->asset_id,
        ':borrower_name' => trim($data->borrower_name),
        ':department' => trim($data->department),
        ':objective' => trim($data->objective),
        ':borrow_date' => $borrowDate,
        ':expected_return_date' => trim($data->expected_return_date)
    ]);

    // Fetch Asset Details for Line Notify
    $stmtAsset = $pdo2->prepare("SELECT asset_code, name FROM assets WHERE id = ?");
    $stmtAsset->execute([$data->asset_id]);
    $asset = $stmtAsset->fetch(PDO::FETCH_ASSOC);
    $assetText = $asset ? "[{$asset['asset_code']}] {$asset['name']}" : "Asset ID: " . $data->asset_id;

    // --- LINE NOTIFY START ---
    $client_key = '151518fbb68266eb98604e010d4ffdb231365f32';
    $secret_key = 'KVQRE5QY6PEDRAQRX6UGQU7QOO5Y';

    $linePayload = [
        "messages" => [
            [
                "type" => "flex",
                "altText" => "New Computer Loan Request: " . trim($data->borrower_name),
                "contents" => [
                    "type" => "bubble",
                    "direction" => "ltr",
                    "header" => [
                        "type" => "box",
                        "layout" => "vertical",
                        "backgroundColor" => "#0d6efd", // Primary blue for loan
                        "contents" => [
                            [
                                "type" => "text",
                                "text" => "💻 ขอยืมอุปกรณ์คอมพิวเตอร์",
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
                                "text" => trim($data->borrower_name),
                                "weight" => "bold",
                                "size" => "xl",
                                "color" => "#0d6efd",
                                "align" => "center",
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
                                            ["type" => "text", "text" => "หน่วยงาน", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                            ["type" => "text", "text" => trim($data->department), "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 5]
                                        ]
                                    ],
                                    [
                                        "type" => "box",
                                        "layout" => "baseline",
                                        "spacing" => "sm",
                                        "contents" => [
                                            ["type" => "text", "text" => "อุปกรณ์", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                            ["type" => "text", "text" => $assetText, "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 5]
                                        ]
                                    ],
                                    [
                                        "type" => "box",
                                        "layout" => "baseline",
                                        "spacing" => "sm",
                                        "contents" => [
                                            ["type" => "text", "text" => "วัตถุประสงค์", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                            ["type" => "text", "text" => trim($data->objective), "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 5]
                                        ]
                                    ],
                                    [
                                        "type" => "box",
                                        "layout" => "baseline",
                                        "spacing" => "sm",
                                        "contents" => [
                                            ["type" => "text", "text" => "กำหนดคืน", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                            ["type" => "text", "text" => trim($data->expected_return_date), "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 5]
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

    echo json_encode(['success' => true, 'message' => 'Loan request submitted successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
