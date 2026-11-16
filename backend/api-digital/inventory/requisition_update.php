<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';
require_once __DIR__ . '/inv_auth_helper.php';
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

$requisition_id = (int)($input['requisition_id'] ?? ($input['id'] ?? 0));
$warehouse_id = (int)($input['warehouse_id'] ?? 0);
$department_id = !empty($input['department_id']) ? (int)$input['department_id'] : null;
$department_name = trim($input['department_name'] ?? '');
$requester_name = trim($input['requester_name'] ?? '');
$requester_phone = trim($input['requester_phone'] ?? '');
$purpose = trim($input['purpose'] ?? '');
$request_date = !empty($input['request_date']) ? $input['request_date'] : date('Y-m-d H:i:s');
$items = $input['items'] ?? [];

if ($requisition_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่พบรหัสใบขอเบิก']);
    exit;
}

if ($warehouse_id <= 0 || empty($requester_name) || empty($items) || !is_array($items)) {
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน กรุณาตรวจสอบคลัง ชื่อผู้ขอเบิก และรายการวัสดุ']);
    exit;
}

try {
    $stExisting = $pdo2->prepare("SELECT * FROM inv_requisitions WHERE id = ? LIMIT 1");
    $stExisting->execute([$requisition_id]);
    $existing = $stExisting->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลใบขอเบิก']);
        exit;
    }

    if ($existing['status'] !== 'PENDING') {
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถแก้ไขได้ เนื่องจากใบขอเบิกนี้ถูกพิจารณาไปแล้ว']);
        exit;
    }

    $currentUser = getInvCurrentUser($pdo3 ?? null);
    $isManager = isInvWarehouseManager($pdo2, $pdo3 ?? null, (int)$existing['warehouse_id'], $currentUser);
    $isOwner = isInvRequisitionOwner($existing, $currentUser);

    if (!$isManager && !$isOwner) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์แก้ไขใบขอเบิกของผู้อื่น']);
        exit;
    }

    // Validate restricted items
    $itemIdsToCheck = array_values(array_unique(array_filter(array_map(function($it) {
        return (int)($it['item_id'] ?? 0);
    }, $items))));
    if (!empty($itemIdsToCheck) && !$isManager) {
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

    $stmtUpd = $pdo2->prepare("
        UPDATE inv_requisitions
        SET warehouse_id = ?, request_date = ?, department_id = ?, department_name = ?,
            requester_name = ?, requester_phone = ?, purpose = ?, updated_at = NOW()
        WHERE id = ? AND status = 'PENDING'
    ");
    $stmtUpd->execute([
        $warehouse_id,
        $request_date,
        $department_id,
        $department_name,
        $requester_name,
        $requester_phone,
        $purpose,
        $requisition_id
    ]);

    $pdo2->prepare("DELETE FROM inv_requisition_items WHERE requisition_id = ?")->execute([$requisition_id]);

    $stmtItem = $pdo2->prepare("
        INSERT INTO inv_requisition_items 
        (requisition_id, item_id, item_code, item_name, unit_name, qty_requested, item_note, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING')
    ");

    foreach ($items as $item) {
        $item_id = (int)($item['item_id'] ?? 0);
        $qty_requested = (float)($item['qty_requested'] ?? ($item['qty'] ?? 0));
        if ($item_id <= 0 || $qty_requested <= 0) continue;

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
        'page_name' => 'แก้ไขคำขอเบิกวัสดุ',
        'action' => 'UPDATE_REQUISITION',
        'target_type' => 'requisition',
        'target_id' => (string)$requisition_id,
        'details' => "แก้ไขคำขอเบิกวัสดุ {$existing['req_no']} (หน่วยงาน: " . ($department_name ?: '-') . ") จำนวน " . count($items) . " รายการ",
        'meta' => ['req_no' => $existing['req_no'], 'department_name' => $department_name, 'items_count' => count($items)],
    ], $pdo3 ?? null);

    echo json_encode([
        'status' => 'success',
        'message' => 'แก้ไขใบขอเบิกพัสดุเรียบร้อยแล้ว',
        'req_no' => $existing['req_no'],
        'requisition_id' => $requisition_id
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

