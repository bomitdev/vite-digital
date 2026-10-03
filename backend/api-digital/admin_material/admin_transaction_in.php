<?php
require __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';
require_once '../../cors.php';

// Secure Auth
$userData = authGuard();

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input data']);
    exit;
}

$material_id = isset($data['material_id']) ? intval($data['material_id']) : 0;
$quantity = isset($data['quantity']) ? intval($data['quantity']) : 0;
$action_date = isset($data['action_date']) ? trim($data['action_date']) : date('Y-m-d H:i:s');
$user_profile_name = isset($data['user_profile_name']) ? trim($data['user_profile_name']) : '';
$reference_dest = isset($data['reference_dest']) ? trim($data['reference_dest']) : '';
$note = isset($data['note']) ? trim($data['note']) : '';

if ($material_id <= 0 || $quantity <= 0 || empty($user_profile_name) || empty($reference_dest)) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน (วัสดุ, จำนวน, ผู้รับผิดชอบ, แหล่งที่มา)']);
    exit;
}

try {
    $pdo2->beginTransaction();

    // 1. ตรวจสอบว่ามีวัสดุนี้อยู่จริง
    $stmtMaterial = $pdo2->prepare("SELECT id, balance, price_per_unit FROM mt_admin_materials WHERE id = :id FOR UPDATE");
    $stmtMaterial->execute([':id' => $material_id]);
    $material = $stmtMaterial->fetch();

    if (!$material) {
        $pdo2->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลวัสดุในระบบ']);
        exit;
    }

    $total_price = $material['price_per_unit'] * $quantity;

    // 2. บันทึก Transaction
    $stmtTx = $pdo2->prepare("
        INSERT INTO mt_admin_transactions (material_id, action_type, quantity, total_price, action_date, user_profile_name, reference_dest, note)
        VALUES (:material_id, 'IN', :quantity, :total_price, :action_date, :user, :dest, :note)
    ");
    $stmtTx->execute([
        ':material_id' => $material_id,
        ':quantity' => $quantity,
        ':total_price' => $total_price,
        ':action_date' => $action_date,
        ':user' => $user_profile_name,
        ':dest' => $reference_dest,
        ':note' => $note
    ]);

    // 3. อัปเดตยอดคงคลัง
    $newBalance = $material['balance'] + $quantity;
    $stmtUpdate = $pdo2->prepare("UPDATE mt_admin_materials SET balance = :balance WHERE id = :id");
    $stmtUpdate->execute([':balance' => $newBalance, ':id' => $material_id]);

    $pdo2->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกการรับเข้าวัสดุสำเร็จ',
        'new_balance' => $newBalance
    ]);
} catch (PDOException $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'DB Error: ' . $e->getMessage()
    ]);
}
