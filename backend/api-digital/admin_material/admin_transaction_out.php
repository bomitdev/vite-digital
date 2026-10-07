<?php
require __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';

// Secure Auth
$userData = authGuard();
require_once '../../cors.php';
require_once __DIR__ . '/admin_log_helper.php';

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
$receiver_name = isset($data['receiver_name']) ? trim($data['receiver_name']) : '';
$reference_dest = isset($data['reference_dest']) ? trim($data['reference_dest']) : '';
$note = isset($data['note']) ? trim($data['note']) : '';

if ($material_id <= 0 || $quantity <= 0 || empty($user_profile_name) || empty($receiver_name) || empty($reference_dest)) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน (วัสดุ, จำนวน, ผู้อนุมัติ, ชื่อผู้รับ, หน่วยงานที่เบิก)']);
    exit;
}

try {
    $pdo2->beginTransaction();

    // 1. ตรวจสอบว่ามีวัสดุนี้อยู่จริง และเช็คยอดสต็อก
    $stmtMaterial = $pdo2->prepare("SELECT id, name, balance, price_per_unit FROM mt_admin_materials WHERE id = :id FOR UPDATE");
    $stmtMaterial->execute([':id' => $material_id]);
    $material = $stmtMaterial->fetch();

    if (!$material) {
        $pdo2->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลวัสดุในระบบ']);
        exit;
    }

    if ($material['balance'] < $quantity) {
        $pdo2->rollBack();
        echo json_encode(['status' => 'error', 'message' => "ยอดคงคลังไม่เพียงพอ (คงเหลือ {$material['balance']} ชิ้น)"]);
        exit;
    }

    // 2. ดึง Lot ทั้งหมดที่ยังมีของเรียงตามวันที่รับเข้า (FIFO)
    $stmtLots = $pdo2->prepare("SELECT * FROM mt_admin_lots WHERE material_id = :id AND remaining_qty > 0 ORDER BY receive_date ASC, id ASC FOR UPDATE");
    $stmtLots->execute([':id' => $material_id]);
    $lots = $stmtLots->fetchAll();

    $remaining_to_fulfill = $quantity;
    $total_out_price = 0;
    $lots_used = [];

    foreach ($lots as $lot) {
        if ($remaining_to_fulfill <= 0) break;

        $take_qty = min($lot['remaining_qty'], $remaining_to_fulfill);
        
        $total_out_price += ($take_qty * $lot['price_per_unit']);
        $remaining_to_fulfill -= $take_qty;
        
        $new_lot_qty = $lot['remaining_qty'] - $take_qty;

        // Update lot remaining_qty
        $stmtUpdateLot = $pdo2->prepare("UPDATE mt_admin_lots SET remaining_qty = :qty WHERE id = :id");
        $stmtUpdateLot->execute([':qty' => $new_lot_qty, ':id' => $lot['id']]);

        // Keep track of which lots we used
        $lots_used[] = [
            'lot_id' => $lot['id'],
            'qty' => $take_qty,
            'price' => $lot['price_per_unit']
        ];
    }

    if ($remaining_to_fulfill > 0) {
        // Should not happen if balance check passed, but just in case of mismatch
        $pdo2->rollBack();
        echo json_encode(['status' => 'error', 'message' => "สต็อก Lot ไม่พอจ่าย กรุณาตรวจสอบข้อมูลตั้งต้น"]);
        exit;
    }

    // 3. บันทึก Transaction
    $stmtTx = $pdo2->prepare("
        INSERT INTO mt_admin_transactions (material_id, action_type, quantity, total_price, action_date, user_profile_name, receiver_name, reference_dest, note)
        VALUES (:material_id, 'OUT', :quantity, :total_price, :action_date, :user, :receiver, :dest, :note)
    ");
    $stmtTx->execute([
        ':material_id' => $material_id,
        ':quantity' => $quantity,
        ':total_price' => $total_out_price,
        ':action_date' => $action_date,
        ':user' => $user_profile_name,
        ':receiver' => $receiver_name,
        ':dest' => $reference_dest,
        ':note' => $note
    ]);
    
    $transaction_id = $pdo2->lastInsertId();

    // 4. บันทึกประวัติการใช้ Lot (Transaction Lots)
    $stmtTxLot = $pdo2->prepare("INSERT INTO mt_admin_transaction_lots (transaction_id, lot_id, quantity, price_per_unit) VALUES (?, ?, ?, ?)");
    foreach ($lots_used as $u) {
        $stmtTxLot->execute([$transaction_id, $u['lot_id'], $u['qty'], $u['price']]);
    }

    // 5. อัปเดตยอดคงคลังหลัก (หักออก)
    $newBalance = $material['balance'] - $quantity;
    $stmtUpdate = $pdo2->prepare("UPDATE mt_admin_materials SET balance = :balance WHERE id = :id");
    $stmtUpdate->execute([':balance' => $newBalance, ':id' => $material_id]);

    $pdo2->commit();

    $username = $userData['name'] ?? $userData['user'] ?? 'Unknown User';
    insertAdminLog($pdo2, $username, 'TRANSACTION_OUT', "จ่ายวัสดุ (ID: $material_id) จำนวน $quantity ชิ้น ให้ $receiver_name ($reference_dest)");

    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกการจ่ายออกวัสดุสำเร็จ',
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
