<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';
require_once __DIR__ . '/repair_auth_helper.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid Request Method");
    }

    $userCtx = getGeneralRepairUserContext($pdo2, $pdo3);

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id <= 0) {
        throw new Exception("ID ไม่ถูกต้อง");
    }

    // Check request ownership if not admin
    if (!$userCtx['is_admin']) {
        $stmtChk = $pdo2->prepare("SELECT requester_id, status FROM general_repair_requests WHERE id = ?");
        $stmtChk->execute([$id]);
        $row = $stmtChk->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new Exception("ไม่พบรายการแจ้งซ่อมที่ต้องการลบ");
        }
        $isOwner = ($userCtx['uid'] > 0 && (int)$row['requester_id'] === $userCtx['uid']);
        if (!$isOwner || $row['status'] !== 'Pending') {
            http_response_code(403);
            echo json_encode([
                'status' => 'error',
                'message' => 'ไม่มีสิทธิ์ลบรายการ: ลบได้เฉพาะ Admin หรือผู้แจ้งเองในขณะที่ยังรอดำเนินการ'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $stmt = $pdo2->prepare("DELETE FROM general_repair_requests WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['status' => 'success', 'message' => 'ลบรายการสำเร็จ']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

