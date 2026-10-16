<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';

$userData = authGuard();

if (!isset($pdo2)) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));
if (empty($data->request_no) && empty($data->id)) {
    echo json_encode(['success' => false, 'message' => 'Missing request identifier']);
    exit;
}

$newDept = trim($data->department ?? '');
if (empty($newDept)) {
    echo json_encode(['success' => false, 'message' => 'กรุณาระบุหน่วยงาน']);
    exit;
}

try {
    if (!empty($data->request_no)) {
        $stmt = $pdo2->prepare("UPDATE mt_it_requests SET department = :dept WHERE request_no = :req_no");
        $stmt->execute([':dept' => $newDept, ':req_no' => $data->request_no]);
    } else {
        $stmt = $pdo2->prepare("UPDATE mt_it_requests SET department = :dept WHERE id = :id");
        $stmt->execute([':dept' => $newDept, ':id' => intval($data->id)]);
    }

    echo json_encode(['success' => true, 'message' => 'อัปเดตหน่วยงานเรียบร้อยแล้ว']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
