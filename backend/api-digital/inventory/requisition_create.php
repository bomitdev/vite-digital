<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';
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
    }

    $pdo2->commit();

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

