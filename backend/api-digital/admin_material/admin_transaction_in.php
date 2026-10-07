<?php
require __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';
require_once '../../cors.php';
require_once __DIR__ . '/admin_log_helper.php';

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

$items = [];
if (isset($data['items']) && is_array($data['items'])) {
    $items = $data['items'];
} else {
    // Fallback for single item
    $items[] = [
        'material_id' => isset($data['material_id']) ? intval($data['material_id']) : 0,
        'quantity' => isset($data['quantity']) ? intval($data['quantity']) : 0,
        'price_per_unit' => isset($data['price_per_unit']) ? floatval($data['price_per_unit']) : 0,
        'action_date' => isset($data['action_date']) ? trim($data['action_date']) : date('Y-m-d H:i:s'),
        'user_profile_name' => isset($data['user_profile_name']) ? trim($data['user_profile_name']) : '',
        'reference_dest' => isset($data['reference_dest']) ? trim($data['reference_dest']) : '',
        'bill_number' => isset($data['bill_number']) ? trim($data['bill_number']) : '',
        'note' => isset($data['note']) ? trim($data['note']) : ''
    ];
}

if (count($items) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีรายการรับเข้า']);
    exit;
}

try {
    $pdo2->beginTransaction();

    foreach ($items as $item) {
        $material_id = intval($item['material_id'] ?? 0);
        $quantity = intval($item['quantity'] ?? 0);
        $price_per_unit = floatval($item['price_per_unit'] ?? 0);
        $action_date = trim($item['action_date'] ?? date('Y-m-d H:i:s'));
        $user_profile_name = trim($item['user_profile_name'] ?? '');
        $reference_dest = trim($item['reference_dest'] ?? '');
        $bill_number = trim($item['bill_number'] ?? '');
        $note = trim($item['note'] ?? '');
        $lot_number = isset($item['lot_number']) && trim($item['lot_number']) !== '' ? trim($item['lot_number']) : null;

        if ($material_id <= 0 || $quantity <= 0 || empty($user_profile_name) || empty($reference_dest)) {
            $pdo2->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน (วัสดุ, จำนวน, ผู้รับผิดชอบ, แหล่งที่มา)']);
            exit;
        }

        // 1. ตรวจสอบว่ามีวัสดุนี้อยู่จริง
        $stmtMaterial = $pdo2->prepare("SELECT id, balance, price_per_unit FROM mt_admin_materials WHERE id = :id FOR UPDATE");
        $stmtMaterial->execute([':id' => $material_id]);
        $material = $stmtMaterial->fetch();

        if (!$material) {
            $pdo2->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลวัสดุในระบบ']);
            exit;
        }

        $final_price = $price_per_unit > 0 ? $price_per_unit : $material['price_per_unit'];
        $total_price = $final_price * $quantity;

        // 2. บันทึก Transaction
        $stmtTx = $pdo2->prepare("
            INSERT INTO mt_admin_transactions (material_id, action_type, quantity, total_price, action_date, user_profile_name, reference_dest, bill_number, note)
            VALUES (:material_id, 'IN', :quantity, :total_price, :action_date, :user, :dest, :bill_number, :note)
        ");
        $stmtTx->execute([
            ':material_id' => $material_id,
            ':quantity' => $quantity,
            ':total_price' => $total_price,
            ':action_date' => $action_date,
            ':user' => $user_profile_name,
            ':dest' => $reference_dest,
            ':bill_number' => $bill_number,
            ':note' => $note
        ]);

        // 2.5 สร้าง Lot ใหม่
        $stmtLot = $pdo2->prepare("
            INSERT INTO mt_admin_lots (material_id, lot_number, price_per_unit, original_qty, remaining_qty, receive_date)
            VALUES (:material_id, :lot_number, :price, :qty1, :qty2, :receive_date)
        ");
        $stmtLot->execute([
            ':material_id' => $material_id,
            ':lot_number' => $lot_number,
            ':price' => $final_price,
            ':qty1' => $quantity,
            ':qty2' => $quantity,
            ':receive_date' => $action_date
        ]);

        // 3. อัปเดตยอดคงคลัง
        $newBalance = $material['balance'] + $quantity;
        $stmtUpdate = $pdo2->prepare("UPDATE mt_admin_materials SET balance = :balance WHERE id = :id");
        $stmtUpdate->execute([':balance' => $newBalance, ':id' => $material_id]);
    }

    $pdo2->commit();

    $username = $userData['name'] ?? $userData['user'] ?? 'Unknown User';
    foreach ($items as $item) {
        $mat_id = intval($item['material_id'] ?? 0);
        $qty = intval($item['quantity'] ?? 0);
        insertAdminLog($pdo2, $username, 'TRANSACTION_IN', "รับเข้าวัสดุ (ID: $mat_id) จำนวน $qty ชิ้น");
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกรายการรับเข้าสำเร็จ'
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
