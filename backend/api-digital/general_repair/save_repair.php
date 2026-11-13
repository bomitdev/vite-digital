<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';
require_once __DIR__ . '/../../moph_notify_helper.php';
require_once __DIR__ . '/daily_repair_notify_service.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid Request Method");
    }

    $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
    $repair_title = trim((string)($_POST['repair_title'] ?? ''));
    $symptom = trim((string)($_POST['symptom'] ?? ''));
    $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
    $category_name = trim((string)($_POST['category_name'] ?? ''));
    $location_id = isset($_POST['location_id']) && $_POST['location_id'] !== '' ? (int)$_POST['location_id'] : null;
    $location_name = trim((string)($_POST['location_name'] ?? ''));
    $priority_id = trim((string)($_POST['priority_id'] ?? '01'));
    $requester_id = isset($_POST['requester_id']) && $_POST['requester_id'] !== '' ? (int)$_POST['requester_id'] : null;
    $requester_name = trim((string)($_POST['requester_name'] ?? ''));
    $requester_phone = trim((string)($_POST['requester_phone'] ?? ''));
    $department_id = trim((string)($_POST['department_id'] ?? ''));
    $department_name = trim((string)($_POST['department_name'] ?? ''));
    $asset_id = trim((string)($_POST['asset_id'] ?? ''));
    $technician_id = isset($_POST['technician_id']) && $_POST['technician_id'] !== '' ? (int)$_POST['technician_id'] : null;
    $technician_name = trim((string)($_POST['technician_name'] ?? ''));
    $technician_comment = trim((string)($_POST['technician_comment'] ?? ''));
    $status = trim((string)($_POST['status'] ?? 'Pending'));
    $cancel_reason = trim((string)($_POST['cancel_reason'] ?? ''));

    if (empty($repair_title)) {
        throw new Exception("กรุณาระบุหัวข้อ / อุปกรณ์ที่ต้องการแจ้งซ่อม");
    }
    if (empty($requester_name)) {
        throw new Exception("กรุณาระบุชื่อผู้แจ้ง");
    }

    // Resolve Location Name if ID provided
    if ($location_id && empty($location_name)) {
        $stmt = $pdo2->prepare("SELECT name FROM general_repair_locations WHERE id = ?");
        $stmt->execute([$location_id]);
        $location_name = (string)$stmt->fetchColumn();
    }

    // Resolve Category Name if ID provided
    if ($category_id && empty($category_name)) {
        $stmt = $pdo2->prepare("SELECT name FROM general_repair_categories WHERE id = ?");
        $stmt->execute([$category_id]);
        $category_name = (string)$stmt->fetchColumn();
    }

    // Priority Map
    $priorityMap = [
        '01' => 'ปกติ',
        '02' => 'ด่วน',
        '03' => 'ด่วนมาก',
        '04' => 'ด่วนที่สุด',
    ];
    $priority_name = $priorityMap[$priority_id] ?? 'ปกติ';

    // File Upload Directory
    $uploadDir = __DIR__ . '/../../uploads/repairs/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    // Handle image_before upload
    $image_before = null;
    if (isset($_FILES['image_before']) && $_FILES['image_before']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo($_FILES['image_before']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed, true)) {
            $fn = 'before_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['image_before']['tmp_name'], $uploadDir . $fn)) {
                $image_before = 'backend/uploads/repairs/' . $fn;
            }
        }
    }

    // Handle image_after upload
    $image_after = null;
    if (isset($_FILES['image_after']) && $_FILES['image_after']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo($_FILES['image_after']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed, true)) {
            $fn = 'after_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['image_after']['tmp_name'], $uploadDir . $fn)) {
                $image_after = 'backend/uploads/repairs/' . $fn;
            }
        }
    }

    if ($id) {
        // UPDATE EXISTING
        $sql = "UPDATE general_repair_requests SET 
                    repair_title = :repair_title,
                    symptom = :symptom,
                    category_id = :category_id,
                    category_name = :category_name,
                    location_id = :location_id,
                    location_name = :location_name,
                    priority_id = :priority_id,
                    priority_name = :priority_name,
                    requester_name = :requester_name,
                    requester_phone = :requester_phone,
                    department_name = :department_name,
                    asset_id = :asset_id,
                    technician_id = :technician_id,
                    technician_name = :technician_name,
                    technician_comment = :technician_comment,
                    status = :status,
                    cancel_reason = :cancel_reason";

        $params = [
            ':repair_title' => $repair_title,
            ':symptom' => $symptom,
            ':category_id' => $category_id,
            ':category_name' => $category_name ?: null,
            ':location_id' => $location_id,
            ':location_name' => $location_name ?: null,
            ':priority_id' => $priority_id,
            ':priority_name' => $priority_name,
            ':requester_name' => $requester_name,
            ':requester_phone' => $requester_phone ?: null,
            ':department_name' => $department_name ?: null,
            ':asset_id' => $asset_id ?: null,
            ':technician_id' => $technician_id,
            ':technician_name' => $technician_name ?: null,
            ':technician_comment' => $technician_comment ?: null,
            ':status' => $status,
            ':cancel_reason' => $cancel_reason ?: null,
            ':id' => $id,
        ];

        if ($image_before) {
            $sql .= ", image_before = :image_before";
            $params[':image_before'] = $image_before;
        }
        if ($image_after) {
            $sql .= ", image_after = :image_after";
            $params[':image_after'] = $image_after;
        }
        if ($status === 'In Progress') {
            $sql .= ", received_at = COALESCE(received_at, NOW())";
        } elseif ($status === 'Completed') {
            $sql .= ", completed_at = COALESCE(completed_at, NOW())";
        }

        $sql .= " WHERE id = :id";
        $stmt = $pdo2->prepare($sql);
        $stmt->execute($params);

        $ticket_no = $pdo2->query("SELECT ticket_no FROM general_repair_requests WHERE id = {$id}")->fetchColumn();

    } else {
        // CREATE NEW TICKET
        $ym = date('Ym');
        $stmtLast = $pdo2->prepare("SELECT ticket_no FROM general_repair_requests WHERE ticket_no LIKE ? ORDER BY id DESC LIMIT 1");
        $stmtLast->execute(["REP-{$ym}-%"]);
        $lastTicket = $stmtLast->fetchColumn();

        if ($lastTicket) {
            $lastSeq = (int)substr($lastTicket, -4);
            $newSeq = str_pad($lastSeq + 1, 4, '0', STR_PAD_LEFT);
        } else {
            // Find global max sequential to ensure nice numbers
            $maxId = (int)$pdo2->query("SELECT MAX(id) FROM general_repair_requests")->fetchColumn();
            $newSeq = str_pad($maxId + 1, 4, '0', STR_PAD_LEFT);
        }
        $ticket_no = "REP-{$ym}-{$newSeq}";

        $stmt = $pdo2->prepare("
            INSERT INTO general_repair_requests (
                ticket_no, repair_title, symptom,
                category_id, category_name, location_id, location_name,
                priority_id, priority_name,
                requester_id, requester_name, requester_phone,
                department_id, department_name, asset_id,
                technician_id, technician_name, technician_comment,
                status, image_before, created_at
            ) VALUES (
                :ticket_no, :repair_title, :symptom,
                :category_id, :category_name, :location_id, :location_name,
                :priority_id, :priority_name,
                :requester_id, :requester_name, :requester_phone,
                :department_id, :department_name, :asset_id,
                :technician_id, :technician_name, :technician_comment,
                :status, :image_before, NOW()
            )
        ");

        $stmt->execute([
            ':ticket_no' => $ticket_no,
            ':repair_title' => $repair_title,
            ':symptom' => $symptom ?: null,
            ':category_id' => $category_id,
            ':category_name' => $category_name ?: null,
            ':location_id' => $location_id,
            ':location_name' => $location_name ?: null,
            ':priority_id' => $priority_id,
            ':priority_name' => $priority_name,
            ':requester_id' => $requester_id,
            ':requester_name' => $requester_name,
            ':requester_phone' => $requester_phone ?: null,
            ':department_id' => $department_id ?: null,
            ':department_name' => $department_name ?: null,
            ':asset_id' => $asset_id ?: null,
            ':technician_id' => $technician_id,
            ':technician_name' => $technician_name ?: null,
            ':technician_comment' => $technician_comment ?: null,
            ':status' => $status,
            ':image_before' => $image_before,
        ]);

        $id = (int)$pdo2->lastInsertId();

        // ==========================================
        // REAL-TIME NOTIFICATION VIA MOPH NOTIFY
        // ==========================================
        $priorityColorMap = [
            '01' => '#6c757d', // ปกติ (เทา)
            '02' => '#fd7e14', // ด่วน (ส้ม)
            '03' => '#e83e8c', // ด่วนมาก (ชมพูเข้ม)
            '04' => '#dc3545', // ด่วนที่สุด (แดง)
        ];
        $headerColor = $priorityColorMap[$priority_id] ?? '#0d6efd';

        $notifyContents = [
            [
                "type" => "box",
                "layout" => "baseline",
                "spacing" => "sm",
                "contents" => [
                    ["type" => "text", "text" => "เลขที่", "color" => "#888888", "size" => "sm", "flex" => 2],
                    ["type" => "text", "text" => $ticket_no, "weight" => "bold", "color" => "#111111", "size" => "sm", "flex" => 4]
                ]
            ],
            [
                "type" => "box",
                "layout" => "baseline",
                "spacing" => "sm",
                "contents" => [
                    ["type" => "text", "text" => "ความเร่งด่วน", "color" => "#888888", "size" => "sm", "flex" => 2],
                    ["type" => "text", "text" => $priority_name, "weight" => "bold", "color" => $headerColor, "size" => "sm", "flex" => 4]
                ]
            ],
            [
                "type" => "box",
                "layout" => "baseline",
                "spacing" => "sm",
                "contents" => [
                    ["type" => "text", "text" => "อุปกรณ์/งาน", "color" => "#888888", "size" => "sm", "flex" => 2],
                    ["type" => "text", "text" => $repair_title, "weight" => "bold", "wrap" => true, "color" => "#0d6efd", "size" => "sm", "flex" => 4]
                ]
            ],
        ];

        if ($symptom !== '') {
            $notifyContents[] = [
                "type" => "box",
                "layout" => "baseline",
                "spacing" => "sm",
                "contents" => [
                    ["type" => "text", "text" => "อาการเสีย", "color" => "#888888", "size" => "sm", "flex" => 2],
                    ["type" => "text", "text" => $symptom, "wrap" => true, "color" => "#444444", "size" => "sm", "flex" => 4]
                ]
            ];
        }

        $locText = $location_name ?: 'ไม่ระบุ';
        $notifyContents[] = [
            "type" => "box",
            "layout" => "baseline",
            "spacing" => "sm",
            "contents" => [
                ["type" => "text", "text" => "สถานที่", "color" => "#888888", "size" => "sm", "flex" => 2],
                ["type" => "text", "text" => $locText, "wrap" => true, "color" => "#444444", "size" => "sm", "flex" => 4]
            ]
        ];

        if ($category_name !== '') {
            $notifyContents[] = [
                "type" => "box",
                "layout" => "baseline",
                "spacing" => "sm",
                "contents" => [
                    ["type" => "text", "text" => "หมวดหมู่", "color" => "#888888", "size" => "sm", "flex" => 2],
                    ["type" => "text", "text" => $category_name, "wrap" => true, "color" => "#444444", "size" => "sm", "flex" => 4]
                ]
            ];
        }

        $reqContact = $requester_name . ($requester_phone ? " (โทร: {$requester_phone})" : "");
        $notifyContents[] = [
            "type" => "box",
            "layout" => "baseline",
            "spacing" => "sm",
            "contents" => [
                ["type" => "text", "text" => "ผู้แจ้ง", "color" => "#888888", "size" => "sm", "flex" => 2],
                ["type" => "text", "text" => $reqContact, "wrap" => true, "color" => "#444444", "size" => "sm", "flex" => 4]
            ]
        ];

        if ($technician_name !== '') {
            $notifyContents[] = [
                "type" => "box",
                "layout" => "baseline",
                "spacing" => "sm",
                "contents" => [
                    ["type" => "text", "text" => "ช่างที่ระบุ", "color" => "#888888", "size" => "sm", "flex" => 2],
                    ["type" => "text", "text" => $technician_name, "wrap" => true, "color" => "#198754", "size" => "sm", "flex" => 4]
                ]
            ];
        }

        $ticketUrl = getRepairWebUrl($pdo2, $ticket_no);
        $altNotify = "แจ้งซ่อมบำรุงทั่วไปใหม่: {$ticket_no} ({$repair_title}) คลิกลิงก์: {$ticketUrl}";

        $mophPayload = [
            "messages" => [
                [
                    "type" => "flex",
                    "altText" => $altNotify,
                    "contents" => [
                        "type" => "bubble",
                        "direction" => "ltr",
                        "header" => [
                            "type" => "box",
                            "layout" => "vertical",
                            "backgroundColor" => $headerColor,
                            "paddingAll" => "16px",
                            "contents" => [
                                [
                                    "type" => "text",
                                    "text" => "🛠️ แจ้งซ่อมบำรุงทั่วไปใหม่",
                                    "weight" => "bold",
                                    "size" => "md",
                                    "color" => "#ffffff",
                                    "align" => "center"
                                ],
                                [
                                    "type" => "text",
                                    "text" => date('d/m/Y H:i น.'),
                                    "size" => "xs",
                                    "color" => "#ffffffcc",
                                    "align" => "center",
                                    "margin" => "xs"
                                ]
                            ]
                        ],
                        "body" => [
                            "type" => "box",
                            "layout" => "vertical",
                            "paddingAll" => "16px",
                            "spacing" => "sm",
                            "contents" => $notifyContents
                        ],
                        "footer" => [
                            "type" => "box",
                            "layout" => "vertical",
                            "spacing" => "sm",
                            "paddingAll" => "16px",
                            "contents" => [
                                [
                                    "type" => "button",
                                    "style" => "primary",
                                    "color" => $headerColor,
                                    "height" => "sm",
                                    "action" => [
                                        "type" => "uri",
                                        "label" => "🔐 เข้าสู่ระบบและรับงานซ่อม",
                                        "uri" => $ticketUrl
                                    ]
                                ],
                                [
                                    "type" => "text",
                                    "text" => $ticketUrl,
                                    "size" => "xxs",
                                    "color" => "#0d6efd",
                                    "align" => "center",
                                    "action" => [
                                        "type" => "uri",
                                        "uri" => $ticketUrl
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        try {
            // Attempt channel 'repair', fall back to 'admin'
            $resNotify = sendMophNotifyToChannel($pdo2, 'repair', $mophPayload);
            if (empty($resNotify['ok'])) {
                // Try fallback to admin channel
                sendMophNotifyToChannel($pdo2, 'admin', $mophPayload);
            }
        } catch (Exception $notifyEx) {
            error_log("MOPH Notify Error in save_repair: " . $notifyEx->getMessage());
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว',
        'data' => [
            'id' => $id,
            'ticket_no' => $ticket_no,
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

