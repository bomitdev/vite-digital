<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$pr_id = isset($data['id']) ? intval($data['id']) : 0;
$level = $data['level'] ?? ''; // 'HEAD' or 'DIR'
$action = $data['action'] ?? ''; // 'APPROVE' or 'REJECT'
$approver_name = $data['approver_name'] ?? 'ผู้มีอำนาจอนุมัติ';
$reason = $data['reason'] ?? '';

if ($pr_id <= 0 || !in_array($level, ['HEAD', 'DIR']) || !in_array($action, ['APPROVE', 'REJECT'])) {
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ถูกต้อง']);
    exit;
}

try {
    $pdo2->beginTransaction();

    // Check current status
    $stmt = $pdo2->prepare("SELECT status FROM inv_purchase_requests WHERE id = ?");
    $stmt->execute([$pr_id]);
    $pr = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pr) {
        throw new Exception("ไม่พบใบขอซื้อนี้");
    }

    $current_status = $pr['status'];
    $new_status = $current_status;

    if ($level === 'HEAD') {
        if ($current_status !== 'PENDING') {
            throw new Exception("ไม่สามารถอนุมัติได้ (สถานะปัจจุบันไม่ใช่ PENDING)");
        }
        if ($action === 'APPROVE') {
            $new_status = 'HEAD_APPROVED';
            $stmt = $pdo2->prepare("UPDATE inv_purchase_requests SET status=?, head_approver_name=?, head_approve_date=NOW() WHERE id=?");
            $stmt->execute([$new_status, $approver_name, $pr_id]);
        } else {
            $new_status = 'HEAD_REJECTED';
            $stmt = $pdo2->prepare("UPDATE inv_purchase_requests SET status=?, head_approver_name=?, head_approve_date=NOW(), head_reject_reason=? WHERE id=?");
            $stmt->execute([$new_status, $approver_name, $reason, $pr_id]);
        }
    } elseif ($level === 'DIR') {
        if ($current_status !== 'HEAD_APPROVED') {
            throw new Exception("ไม่สามารถอนุมัติได้ (สถานะปัจจุบันไม่ใช่ HEAD_APPROVED)");
        }
        if ($action === 'APPROVE') {
            $new_status = 'DIR_APPROVED';
            $stmt = $pdo2->prepare("UPDATE inv_purchase_requests SET status=?, dir_approver_name=?, dir_approve_date=NOW() WHERE id=?");
            $stmt->execute([$new_status, $approver_name, $pr_id]);
        } else {
            $new_status = 'DIR_REJECTED';
            $stmt = $pdo2->prepare("UPDATE inv_purchase_requests SET status=?, dir_approver_name=?, dir_approve_date=NOW(), dir_reject_reason=? WHERE id=?");
            $stmt->execute([$new_status, $approver_name, $reason, $pr_id]);
        }
    }

    $pdo2->commit();
    echo json_encode(['status' => 'success', 'message' => 'ดำเนินการสำเร็จ', 'new_status' => $new_status]);

} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
