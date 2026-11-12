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

$requisition_id = (int)($input['requisition_id'] ?? ($input['id'] ?? 0));
$approver_name = trim($input['approver_name'] ?? 'ผู้ดูแลระบบ');
$reject_reason = trim($input['reject_reason'] ?? ($input['reason'] ?? 'ไม่อนุมัติ'));

if ($requisition_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่พบรหัสใบขอเบิก']);
    exit;
}

try {
    $stmt = $pdo2->prepare("
        UPDATE inv_requisitions 
        SET status = 'REJECTED', approver_name = ?, approved_date = NOW(), admin_note = ? 
        WHERE id = ? AND status = 'PENDING'
    ");
    $stmt->execute([$approver_name, $reject_reason, $requisition_id]);

    if ($stmt->rowCount() > 0) {
        $stmtItems = $pdo2->prepare("UPDATE inv_requisition_items SET status = 'REJECTED' WHERE requisition_id = ?");
        $stmtItems->execute([$requisition_id]);

        $stInfo = $pdo2->prepare("SELECT warehouse_id, req_no FROM inv_requisitions WHERE id = ?");
        $stInfo->execute([$requisition_id]);
        $rInfo = $stInfo->fetch(PDO::FETCH_ASSOC);
        recordInvActivityLog($pdo2, [
            'warehouse_id' => !empty($rInfo['warehouse_id']) ? (int)$rInfo['warehouse_id'] : null,
            'user_fullname' => $approver_name,
            'page_code' => 'requisitions',
            'page_name' => 'รายการเบิกวัสดุ',
            'action' => 'REJECT_REQUISITION',
            'target_type' => 'requisition',
            'target_id' => (string)$requisition_id,
            'details' => "ปฏิเสธคำขอเบิก " . ($rInfo['req_no'] ?? "#{$requisition_id}") . " เหตุผล: {$reject_reason}",
            'meta' => ['reason' => $reject_reason],
        ], $pdo3 ?? null);

        echo json_encode(['status' => 'success', 'message' => 'ปฏิเสธคำขอเบิกเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถปฏิเสธได้ (อาจได้รับการอนุมัติหรือยกเลิกไปแล้ว)']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

