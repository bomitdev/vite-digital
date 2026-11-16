<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';
require_once __DIR__ . '/../../auth_utils.php';
require_once __DIR__ . '/../../moph_notify_helper.php';
require_once __DIR__ . '/inv_log_helper.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST method is allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$warehouse_id = (int)($input['warehouse_id'] ?? 0);
$department_id = !empty($input['department_id']) ? (int)$input['department_id'] : null;
$department_name = trim($input['department_name'] ?? '');
$requester_name = trim($input['requester_name'] ?? '');
$requester_phone = trim($input['requester_phone'] ?? '');
$purpose = trim($input['purpose'] ?? '');
$request_date = !empty($input['request_date']) ? $input['request_date'] : date('Y-m-d H:i:s');
$items = $input['items'] ?? [];

if ($warehouse_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุคลังวัสดุ']);
    exit;
}

if (empty($requester_name)) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุชื่อผู้ขอเบิก']);
    exit;
}

if (empty($items) || !is_array($items)) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุรายการวัสดุที่ต้องการขอเบิกอย่างน้อย 1 รายการ']);
    exit;
}

try {
    // Check if current user has warehouse manager/admin access for this warehouse
    $isWarehouseManager = false;
    $tokenUser = (function_exists('authOptional') && function_exists('getallheaders')) ? authOptional() : null;
    $authUid = (int)($tokenUser['uid'] ?? 0);
    if ($authUid > 0) {
        if (isset($pdo3)) {
            $stAdm = $pdo3->prepare("SELECT access_user FROM hr_person WHERE ID = ? LIMIT 1");
            $stAdm->execute([$authUid]);
            $accStr = (string)$stAdm->fetchColumn();
            $perms = array_filter(array_map('trim', explode(':', $accStr)));
            if (!empty(array_intersect($perms, ['administrator', 'menu_inventory_admin', 'menu_inventory_manage']))) {
                $isWarehouseManager = true;
            }
        }
        if (!$isWarehouseManager) {
            $stWhUser = $pdo2->prepare("SELECT access_level FROM inv_user_warehouses WHERE warehouse_id = ? AND user_id = ? LIMIT 1");
            $stWhUser->execute([$warehouse_id, $authUid]);
            if ($stWhUser->fetchColumn()) {
                $isWarehouseManager = true;
            }
        }
    }

    // Validate restricted items before creating requisition
    $itemIdsToCheck = array_values(array_unique(array_filter(array_map(function($it) {
        return (int)($it['item_id'] ?? 0);
    }, $items))));
    if (!empty($itemIdsToCheck) && !$isWarehouseManager) {
        $placeholders = implode(',', array_fill(0, count($itemIdsToCheck), '?'));
        $stRestricted = $pdo2->prepare("SELECT item_name FROM inv_items WHERE id IN ($placeholders) AND admin_only_issue = 1 LIMIT 1");
        $stRestricted->execute($itemIdsToCheck);
        $restrictedName = $stRestricted->fetchColumn();
        if ($restrictedName !== false) {
            echo json_encode([
                'status' => 'error',
                'message' => "พัสดุ \"{$restrictedName}\" ถูกจำกัดสิทธิ์ให้เฉพาะผู้ดูแลคลังเบิกได้เท่านั้น"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $pdo2->beginTransaction();

    // Generate unique requisition number: RQ-YYMMDD-XXXX
    $prefix = 'RQ' . date('ymd');
    $stmtCount = $pdo2->prepare("SELECT COUNT(*) FROM inv_requisitions WHERE req_no LIKE ?");
    $stmtCount->execute([$prefix . '%']);
    $count = (int)$stmtCount->fetchColumn();
    $seq = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    $req_no = $prefix . '-' . $seq;

    // Double check uniqueness
    $stmtCheck = $pdo2->prepare("SELECT id FROM inv_requisitions WHERE req_no = ?");
    $stmtCheck->execute([$req_no]);
    if ($stmtCheck->fetch()) {
        $req_no = $prefix . '-' . str_pad($count + rand(2, 99), 4, '0', STR_PAD_LEFT);
    }

    // Insert header
    $stmt = $pdo2->prepare("
        INSERT INTO inv_requisitions 
        (req_no, warehouse_id, request_date, department_id, department_name, requester_name, requester_phone, purpose, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'PENDING')
    ");
    $stmt->execute([
        $req_no,
        $warehouse_id,
        $request_date,
        $department_id,
        $department_name,
        $requester_name,
        $requester_phone,
        $purpose
    ]);
    $requisition_id = (int)$pdo2->lastInsertId();

    // Insert items
    $stmtItem = $pdo2->prepare("
        INSERT INTO inv_requisition_items 
        (requisition_id, item_id, item_code, item_name, unit_name, qty_requested, item_note, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING')
    ");

    $lineItems = [];
    foreach ($items as $item) {
        $item_id = (int)($item['item_id'] ?? 0);
        $qty_requested = (float)($item['qty_requested'] ?? ($item['qty'] ?? 0));
        if ($item_id <= 0 || $qty_requested <= 0) continue;

        // Fetch item details if not passed
        $item_code = trim($item['item_code'] ?? '');
        $item_name = trim($item['item_name'] ?? '');
        $unit_name = trim($item['unit_name'] ?? '');

        if (empty($item_name) || empty($item_code)) {
            $stmtM = $pdo2->prepare("
                SELECT i.item_code, i.item_name, u.name as unit_name 
                FROM inv_items i 
                LEFT JOIN inv_units u ON i.unit_id = u.id 
                WHERE i.id = ?
            ");
            $stmtM->execute([$item_id]);
            $m = $stmtM->fetch(PDO::FETCH_ASSOC);
            if ($m) {
                $item_code = $m['item_code'];
                $item_name = $m['item_name'];
                $unit_name = $m['unit_name'] ?: 'หน่วย';
            }
        }

        $item_note = trim($item['item_note'] ?? ($item['remark'] ?? ''));

        $stmtItem->execute([
            $requisition_id,
            $item_id,
            $item_code,
            $item_name,
            $unit_name,
            $qty_requested,
            $item_note
        ]);

        $qtyDisplay = (floor($qty_requested) == $qty_requested) ? (string)(int)$qty_requested : (string)$qty_requested;
        $unitStr = !empty($unit_name) ? ' ' . $unit_name : '';
        $lineItems[] = [
            "type" => "box",
            "layout" => "baseline",
            "spacing" => "sm",
            "contents" => [
                ["type" => "text", "text" => "- " . $item_name, "wrap" => true, "color" => "#555555", "size" => "sm", "flex" => 4],
                ["type" => "text", "text" => $qtyDisplay . $unitStr, "color" => "#0d6efd", "size" => "sm", "flex" => 2, "align" => "end", "weight" => "bold"]
            ]
        ];
    }

    $pdo2->commit();

    // --- MOPH NOTIFY START ---
    try {
        $stWh = $pdo2->prepare("SELECT code, name, notify_channel FROM inv_warehouses WHERE id = ? LIMIT 1");
        $stWh->execute([$warehouse_id]);
        $whInfo = $stWh->fetch(PDO::FETCH_ASSOC) ?: ['code' => '', 'name' => 'คลังวัสดุรวมศูนย์', 'notify_channel' => ''];
        $whCode = trim((string)($whInfo['code'] ?? ''));
        $whName = trim((string)($whInfo['name'] ?? 'คลังวัสดุรวมศูนย์'));
        $channelCode = trim((string)($whInfo['notify_channel'] ?? ''));

        if ($channelCode !== '') {
            $displayDate = !empty($request_date) ? date('d/m/Y H:i น.', strtotime($request_date)) : date('d/m/Y H:i น.');
        $headerLines = [
            [
                "type" => "box",
                "layout" => "baseline",
                "spacing" => "sm",
                "contents" => [
                    ["type" => "text", "text" => "เลขที่คำขอ", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                    ["type" => "text", "text" => $req_no, "wrap" => true, "color" => "#0d6efd", "size" => "sm", "weight" => "bold", "flex" => 5]
                ]
            ],
            [
                "type" => "box",
                "layout" => "baseline",
                "spacing" => "sm",
                "contents" => [
                    ["type" => "text", "text" => "คลังที่เบิก", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                    ["type" => "text", "text" => ($whCode ? "[{$whCode}] " : '') . $whName, "wrap" => true, "color" => "#198754", "size" => "sm", "weight" => "bold", "flex" => 5]
                ]
            ],
            [
                "type" => "box",
                "layout" => "baseline",
                "spacing" => "sm",
                "contents" => [
                    ["type" => "text", "text" => "ผู้ขอเบิก", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                    ["type" => "text", "text" => $requester_name, "wrap" => true, "color" => "#333333", "size" => "sm", "weight" => "bold", "flex" => 5]
                ]
            ],
            [
                "type" => "box",
                "layout" => "baseline",
                "spacing" => "sm",
                "contents" => [
                    ["type" => "text", "text" => "หน่วยงาน", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                    ["type" => "text", "text" => ($department_name ?: '-'), "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 5]
                ]
            ],
            [
                "type" => "box",
                "layout" => "baseline",
                "spacing" => "sm",
                "contents" => [
                    ["type" => "text", "text" => "วันที่ขอเบิก", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                    ["type" => "text", "text" => $displayDate, "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 5]
                ]
            ]
        ];

        if ($purpose !== '') {
            $headerLines[] = [
                "type" => "box",
                "layout" => "baseline",
                "spacing" => "sm",
                "contents" => [
                    ["type" => "text", "text" => "เหตุผล/หมายเหตุ", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                    ["type" => "text", "text" => $purpose, "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 5]
                ]
            ];
        }

        $linePayload = [
            "messages" => [
                [
                    "type" => "flex",
                    "altText" => "คำขอเบิกวัสดุใหม่ ({$whName}) เลขที่: {$req_no}",
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
                                    "text" => "📦 คำขอเบิกวัสดุใหม่ (" . count($lineItems) . " รายการ)",
                                    "weight" => "bold",
                                    "size" => "md",
                                    "color" => "#ffffff",
                                    "align" => "center"
                                ],
                                [
                                    "type" => "text",
                                    "text" => ($whCode ? "[{$whCode}] " : '') . $whName,
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
                                    "contents" => $headerLines
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

            $notifyRes = sendMophNotifyToChannel($pdo2, $channelCode, $linePayload);
            error_log("inv requisition_create Notify Result ({$channelCode}): HTTP {$notifyRes['http_code']}, Err: {$notifyRes['curl_error']}");
        }
    } catch (Exception $notifyEx) {
        error_log("MOPH Notify Error in requisition_create: " . $notifyEx->getMessage());
    }
    // --- MOPH NOTIFY END ---

    recordInvActivityLog($pdo2, [
        'warehouse_id' => $warehouse_id,
        'user_fullname' => $requester_name,
        'page_code' => 'requisition_form',
        'page_name' => 'สร้างคำขอเบิกวัสดุ',
        'action' => 'CREATE_REQUISITION',
        'target_type' => 'requisition',
        'target_id' => (string)$requisition_id,
        'details' => "สร้างคำขอเบิกวัสดุ {$req_no} (หน่วยงาน: " . ($department_name ?: '-') . ") จำนวน " . count($items) . " รายการ",
        'meta' => ['req_no' => $req_no, 'department_name' => $department_name, 'items_count' => count($items)],
    ], $pdo3 ?? null);

    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกใบขอเบิกพัสดุเรียบร้อยแล้ว',
        'req_no' => $req_no,
        'requisition_id' => $requisition_id
    ]);
} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}

