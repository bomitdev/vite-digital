<?php
require_once '../../config.php';
require_once '../../cors.php';
require_once __DIR__ . '/../../auth_utils.php';
require_once __DIR__ . '/pharmacy_log_helper.php';

$userData = authGuard();

header("Content-Type: application/json");

// รับข้อมูล JSON
$data = json_decode(file_get_contents("php://input"), true);
$transaction_id = isset($data['id']) ? intval($data['id']) : 0;
$user_name = isset($data['user_name']) ? trim($data['user_name']) : '';

if ($transaction_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่ระบุรหัสรายการ']);
    exit;
}

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

try {
    $pdo2->beginTransaction();

    // 1. ดึงข้อมูล Transaction
    $stmtTx = $pdo2->prepare("SELECT material_id, action_type, quantity, action_date FROM pharmacy_transactions WHERE id = :id FOR UPDATE");
    $stmtTx->execute([':id' => $transaction_id]);
    $tx = $stmtTx->fetch(PDO::FETCH_ASSOC);

    if (!$tx) {
        $pdo2->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบรายการที่ต้องการยกเลิก']);
        exit;
    }

    $material_id = $tx['material_id'];
    $action_type = $tx['action_type'];
    $quantity = intval($tx['quantity']);

    // 2. ดึงข้อมูล Material เพื่อเช็ค Balance ปัจจุบัน
    $stmtMat = $pdo2->prepare("SELECT balance FROM pharmacy_materials WHERE id = :id FOR UPDATE");
    $stmtMat->execute([':id' => $material_id]);
    $material = $stmtMat->fetch(PDO::FETCH_ASSOC);

    if (!$material) {
        $pdo2->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลวัสดุในระบบแล้ว']);
        exit;
    }

    $current_balance = intval($material['balance']);
    $new_balance = $current_balance;

    // 3. ปรับปรุงยอดคงเหลือ (ย้อนกลับการกระทำ)
    if ($action_type === 'IN') {
        // ถ้ายกเลิกการรับเข้า -> ต้องลบยอดออก
        $new_balance -= $quantity;
        if ($new_balance < 0) {
            $pdo2->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถยกเลิกได้ เนื่องจากยอดคงเหลือปัจจุบันจะติดลบ']);
            exit;
        }
    } else if ($action_type === 'OUT') {
        // ถ้ายกเลิกการเบิกจ่าย -> ต้องคืนยอดเข้าคลัง
        $new_balance += $quantity;
    }

    // 4. บันทึกยอดคงเหลือใหม่
    $stmtUpdateMat = $pdo2->prepare("UPDATE pharmacy_materials SET balance = :balance, updated_at = NOW() WHERE id = :id");
    $stmtUpdateMat->execute([
        ':balance' => $new_balance,
        ':id' => $material_id
    ]);

    // 4.5 จัดการระบบ Lot
    if ($action_type === 'IN') {
        // ค้นหา Lot ที่เกี่ยวข้องและลบทิ้ง (อิงจาก material_id, action_date, original_qty)
        $stmtFindLot = $pdo2->prepare("SELECT id, remaining_qty, original_qty FROM pharmacy_lots WHERE material_id = :id AND receive_date = :dt AND original_qty = :qty LIMIT 1");
        $stmtFindLot->execute([':id' => $material_id, ':dt' => $tx['action_date'], ':qty' => $quantity]);
        $lot = $stmtFindLot->fetch();
        if ($lot) {
            if ($lot['remaining_qty'] < $lot['original_qty']) {
                $pdo2->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถยกเลิกได้ เนื่องจาก Lot นี้ถูกเบิกจ่ายไปแล้วบางส่วน']);
                exit;
            }
            $pdo2->prepare("DELETE FROM pharmacy_lots WHERE id = :id")->execute([':id' => $lot['id']]);
        }
    } else if ($action_type === 'OUT') {
        // คืนของเข้า Lot
        $stmtTxLots = $pdo2->prepare("SELECT lot_id, quantity FROM pharmacy_transaction_lots WHERE transaction_id = :tx_id");
        $stmtTxLots->execute([':tx_id' => $transaction_id]);
        $used_lots = $stmtTxLots->fetchAll();

        $stmtRestoreLot = $pdo2->prepare("UPDATE pharmacy_lots SET remaining_qty = remaining_qty + :qty WHERE id = :lot_id");
        foreach ($used_lots as $ul) {
            $stmtRestoreLot->execute([':qty' => $ul['quantity'], ':lot_id' => $ul['lot_id']]);
        }
        
        $pdo2->prepare("DELETE FROM pharmacy_transaction_lots WHERE transaction_id = :tx_id")->execute([':tx_id' => $transaction_id]);
    }

    // 5. ลบรายการ Transaction ทิ้ง
    $stmtDeleteTx = $pdo2->prepare("DELETE FROM pharmacy_transactions WHERE id = :id");
    $stmtDeleteTx->execute([':id' => $transaction_id]);

    $username = $userData['name'] ?? $userData['user'] ?? 'Unknown User';
    insertAdminLog($pdo2, $username, 'DELETE_TRANSACTION', "ยกเลิกรายการ $action_type ของวัสดุ ID: $material_id จำนวน $quantity ชิ้น");

    $pdo2->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'ยกเลิกรายการสำเร็จ และปรับปรุงยอดคงคลังแล้ว',
        'new_balance' => $new_balance
    ]);
} catch (PDOException $e) {
    $pdo2->rollBack();
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'DB Error: ' . $e->getMessage()
    ]);
}
