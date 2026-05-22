<?php
require_once '../../config.php';
require_once '../../cors.php';

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
    $stmtTx = $pdo2->prepare("SELECT material_id, action_type, quantity FROM mt_transactions WHERE id = :id FOR UPDATE");
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
    $stmtMat = $pdo2->prepare("SELECT balance FROM mt_materials WHERE id = :id FOR UPDATE");
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
    $stmtUpdateMat = $pdo2->prepare("UPDATE mt_materials SET balance = :balance, updated_at = NOW() WHERE id = :id");
    $stmtUpdateMat->execute([
        ':balance' => $new_balance,
        ':id' => $material_id
    ]);

    // 5. ลบรายการ Transaction ทิ้ง
    $stmtDeleteTx = $pdo2->prepare("DELETE FROM mt_transactions WHERE id = :id");
    $stmtDeleteTx->execute([':id' => $transaction_id]);

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
