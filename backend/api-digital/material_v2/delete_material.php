<?php
require_once '../../config.php';
require_once '../../cors.php';

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
    // Check if there are transactions for this material
    $checkTx = $pdo2->prepare("SELECT id FROM mt_transactions WHERE material_id = :id LIMIT 1");
    $checkTx->execute([':id' => $id]);
    if ($checkTx->fetch()) {
        // If there are transactions, we might not want to delete, or we delete with CASCADE (defined in schema).
        // Let's allow deletion with CASCADE, or return an error if you want to strictly keep history.
        // I will allow deletion because ON DELETE CASCADE is set.
    }

    $stmt = $pdo2->prepare("DELETE FROM mt_materials WHERE id = :id");
    $stmt->execute([':id' => $id]);

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
