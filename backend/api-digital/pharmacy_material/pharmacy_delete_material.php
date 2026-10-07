<?php
require_once '../../config.php';
require_once '../../cors.php';
require_once __DIR__ . '/../../auth_utils.php';
require_once __DIR__ . '/pharmacy_log_helper.php';

$userData = authGuard();

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input data']);
    exit;
}

$id = intval($data['id']);

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID ไม่ถูกต้อง']);
    exit;
}

try {
    $checkMat = $pdo2->prepare("SELECT code, name FROM pharmacy_materials WHERE id = :id");
    $checkMat->execute([':id' => $id]);
    $mat = $checkMat->fetch();
    $matName = $mat ? "[{$mat['code']}] {$mat['name']}" : "ID: $id";

    // Check if there are transactions for this material
    $checkTx = $pdo2->prepare("SELECT id FROM pharmacy_transactions WHERE material_id = :id LIMIT 1");
    $checkTx->execute([':id' => $id]);
    if ($checkTx->fetch()) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'รายการนี้มีการรับเข้าหรือเบิกจ่ายแล้ว ไม่สามารถลบได้! กรุณาใช้การ "ปิดการใช้งาน" (สวิตช์สถานะ) แทนการลบเพื่อเก็บประวัติ'
        ]);
        exit;
    }

    $stmt = $pdo2->prepare("DELETE FROM pharmacy_materials WHERE id = :id");
    $stmt->execute([':id' => $id]);

    $username = $userData['name'] ?? $userData['user'] ?? 'Unknown User';
    insertAdminLog($pdo2, $username, 'DELETE_MATERIAL', "ลบวัสดุ: $matName");

    echo json_encode([
        'status' => 'success',
        'message' => 'ลบข้อมูลวัสดุสำเร็จ'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'DB Error: ' . $e->getMessage()
    ]);
}
