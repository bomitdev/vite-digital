<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';

// Secure Auth
$userData = authGuard();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid Request Method");
    }

    // Capture POST data
    $id = isset($_POST['id']) ? $_POST['id'] : null;
    $requester_name = isset($_POST['requester_name']) ? $_POST['requester_name'] : '';
    $department = isset($_POST['department']) ? $_POST['department'] : '';
    $contact_tel = isset($_POST['contact_tel']) ? $_POST['contact_tel'] : '';
    $issue_title = isset($_POST['issue_title']) ? $_POST['issue_title'] : '';
    $issue_description = isset($_POST['issue_description']) ? $_POST['issue_description'] : '';
    $asset_code = isset($_POST['asset_code']) ? $_POST['asset_code'] : null;
    $location = isset($_POST['location']) ? $_POST['location'] : '';

    // Status update (mostly for admin/technician)
    $status = isset($_POST['status']) ? $_POST['status'] : null;
    $technician_name = isset($_POST['technician_name']) ? $_POST['technician_name'] : null;
    $technician_comment = isset($_POST['technician_comment']) ? $_POST['technician_comment'] : null;
    $completed_at = isset($_POST['completed_at']) ? $_POST['completed_at'] : null;

    // Handle Image Upload
    // Handle Image Upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (in_array($extension, $allowed)) {
            $wsRoot = realpath(__DIR__ . '/../../');
            if (!$wsRoot) $wsRoot = __DIR__ . '/../../';

            $uploadDir = $wsRoot . '/uploads/repair/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $filename = 'repair_' . uniqid() . '.' . $extension;
            $targetFile = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $image_path = 'backend/uploads/repair/' . $filename;
            }
        }
    }

    // Determine Action: Update or Create
    if ($id) {
        // UPDATE
        $sql = "UPDATE computer_repair_requests SET updated_at = NOW()";
        $params = [];

        // Dynamic update fields
        if ($status) {
            $sql .= ", status = ?";
            $params[] = $status;
        }
        if ($technician_name) {
            $sql .= ", technician_name = ?";
            $params[] = $technician_name;
        }
        if ($technician_comment) {
            $sql .= ", technician_comment = ?";
            $params[] = $technician_comment;
        }
        if (isset($_POST['completed_at'])) {
            if ($completed_at === '') {
                $sql .= ", completed_at = NULL";
            } else {
                $sql .= ", completed_at = ?";
                $params[] = $completed_at;
            }
        }
        // Allow updating details only if status is Pending? Or always? Let's allow typically editable fields
        if ($issue_title) {
            $sql .= ", issue_title = ?";
            $params[] = $issue_title;
        }
        if ($issue_description) {
            $sql .= ", issue_description = ?";
            $params[] = $issue_description;
        }
        if ($image_path) {
            $sql .= ", image_path = ?";
            $params[] = $image_path;
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $pdo2->prepare($sql);
        $stmt->execute($params);
    } else {
        // CREATE
        // Generate Ticket No
        $yearMonth = date('Ym'); // 202402
        // Find last ticket
        $stmt = $pdo2->prepare("SELECT ticket_no FROM computer_repair_requests WHERE ticket_no LIKE ? ORDER BY id DESC LIMIT 1");
        $stmt->execute(["R-$yearMonth-%"]);
        $lastTicket = $stmt->fetchColumn();

        if ($lastTicket) {
            $lastNum = intval(substr($lastTicket, -4));
            $newNum = str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNum = '0001';
        }
        $ticket_no = "R-$yearMonth-$newNum";

        $sql = "INSERT INTO computer_repair_requests 
        (ticket_no, requester_name, department, contact_tel, asset_code, issue_title, issue_description, location, image_path, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())";

        $pdo2->prepare($sql)->execute([
            $ticket_no,
            $requester_name,
            $department,
            $contact_tel,
            $asset_code,
            $issue_title,
            $issue_description,
            $location,
            $image_path
        ]);

        // --- LINE NOTIFY START ---
        $client_key = '151518fbb68266eb98604e010d4ffdb231365f32';
        $secret_key = 'KVQRE5QY6PEDRAQRX6UGQU7QOO5Y';

        $linePayload = [
            "messages" => [
                [
                    "type" => "flex",
                    "altText" => "New Computer Repair Request: $ticket_no",
                    "contents" => [
                        "type" => "bubble",
                        "direction" => "ltr",
                        "header" => [
                            "type" => "box",
                            "layout" => "vertical",
                            "backgroundColor" => "#dc3545",
                            "contents" => [
                                [
                                    "type" => "text",
                                    "text" => "🔧 แจ้งซ่อมคอมพิวเตอร์ใหม่",
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
                                    "text" => $ticket_no,
                                    "weight" => "bold",
                                    "size" => "xl",
                                    "color" => "#dc3545",
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
                                                ["type" => "text", "text" => "ผู้แจ้ง", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                                ["type" => "text", "text" => $requester_name, "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 4]
                                            ]
                                        ],
                                        [
                                            "type" => "box",
                                            "layout" => "baseline",
                                            "spacing" => "sm",
                                            "contents" => [
                                                ["type" => "text", "text" => "ปัญหา", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                                ["type" => "text", "text" => $issue_title, "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 4]
                                            ]
                                        ],
                                        [
                                            "type" => "box",
                                            "layout" => "baseline",
                                            "spacing" => "sm",
                                            "contents" => [
                                                ["type" => "text", "text" => "สถานที่", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                                ["type" => "text", "text" => $location . " (" . $contact_tel . ")", "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 4]
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
    }

    echo json_encode(['status' => 'success', 'message' => 'Saved successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
