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

if ($requisition_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่พบรหัสใบขอเบิก']);
    exit;
}

try {
    $stmtCheck = $pdo2->prepare("SELECT status, warehouse_id, req_no FROM inv_requisitions WHERE id = ?");
    $stmtCheck->execute([$requisition_id]);
    $reqRow = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    $st = $reqRow['status'] ?? false;

    if (!$st) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูล']);
        exit;
    }

    if ($st === 'APPROVED') {
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบใบขอเบิกที่อนุมัติและตัดสต๊อกไปแล้วได้']);
        exit;
    }

    $pdo2->beginTransaction();
    $stmtDelItems = $pdo2->prepare("DELETE FROM inv_requisition_items WHERE requisition_id = ?");
    $stmtDelItems->execute([$requisition_id]);

    $stmtDelReq = $pdo2->prepare("DELETE FROM inv_requisitions WHERE id = ?");
    $stmtDelReq->execute([$requisition_id]);
    $pdo2->commit();

    recordInvActivityLog($pdo2, [
        'warehouse_id' => !empty($reqRow['warehouse_id']) ? (int)$reqRow['warehouse_id'] : null,
        'page_code' => 'requisitions',
        'page_name' => 'รายการเบิกวัสดุ',
        'action' => 'DELETE_REQUISITION',
        'target_type' => 'requisition',
        'target_id' => (string)$requisition_id,
        'details' => "ลบคำขอเบิกวัสดุ " . ($reqRow['req_no'] ?? "#{$requisition_id}"),
    ], $pdo3 ?? null);

    echo json_encode(['status' => 'success', 'message' => 'ลบใบขอเบิกสำเร็จ']);
} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

