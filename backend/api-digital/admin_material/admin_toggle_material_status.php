<?php
require_once '../../config.php';
require_once '../../cors.php';
require_once __DIR__ . '/../../auth_utils.php';
require_once __DIR__ . '/admin_log_helper.php';

$userData = authGuard();

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['id']) || !isset($data['is_active'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input data']);
    exit;
}

$id = intval($data['id']);
$is_active = intval($data['is_active']);

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID ไม่ถูกต้อง']);
    exit;
}

try {
    $checkMat = $pdo2->prepare("SELECT code, name FROM mt_admin_materials WHERE id = :id");
    $checkMat->execute([':id' => $id]);
    $mat = $checkMat->fetch();
    $matName = $mat ? "[{$mat['code']}] {$mat['name']}" : "ID: $id";

    $stmt = $pdo2->prepare("UPDATE mt_admin_materials SET is_active = :is_active WHERE id = :id");
    $stmt->execute([':is_active' => $is_active, ':id' => $id]);

    $username = $userData['name'] ?? $userData['user'] ?? 'Unknown User';
    $statusText = $is_active ? 'เปิดใช้งาน' : 'ปิดการใช้งาน';
    insertAdminLog($pdo2, $username, 'UPDATE_MATERIAL', "เปลี่ยนสถานะเป็น $statusText: $matName");

    echo json_encode(['status' => 'success', 'message' => "อัปเดตสถานะเป็น $statusText สำเร็จ"]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
}
