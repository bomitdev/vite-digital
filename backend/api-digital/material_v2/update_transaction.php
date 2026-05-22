<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$id = isset($data['id']) ? intval($data['id']) : 0;

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่ระบุรหัสรายการ']);
    exit;
}

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

try {
    $pdo2->beginTransaction();

    // ดึงข้อมูลเดิม
    $stmtTx = $pdo2->prepare("SELECT material_id, action_type, quantity FROM mt_transactions WHERE id = :id FOR UPDATE");
    $stmtTx->execute([':id' => $id]);
    $tx = $stmtTx->fetch(PDO::FETCH_ASSOC);

    if (!$tx) {
        $pdo2->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบรายการที่ต้องการแก้ไข']);
        exit;
    }

    $old_quantity = intval($tx['quantity']);
    $new_quantity = isset($data['quantity']) ? intval($data['quantity']) : $old_quantity;
    $action_type = $tx['action_type'];
    $material_id = $tx['material_id'];

    // เช็ควัสดุและคำนวณยอด
    $stmtMat = $pdo2->prepare("SELECT balance FROM mt_materials WHERE id = :id FOR UPDATE");
    $stmtMat->execute([':id' => $material_id]);
    $mat = $stmtMat->fetch(PDO::FETCH_ASSOC);

    $current_balance = intval($mat['balance']);

    if ($new_quantity != $old_quantity) {
        $diff = $new_quantity - $old_quantity; // ถ้าบวกคือจำนวนที่ทำรายการเพิ่มขึ้น
        $new_balance = $current_balance;

        if ($action_type === 'IN') {
            // รับเข้ามากขึ้น Diff เป็นบวก -> สต็อกเพิ่ม
            $new_balance += $diff;
        } else if ($action_type === 'OUT') {
            // จ่ายออกมากขึ้น Diff เป็นบวก -> สต็อกลดลง
            $new_balance -= $diff;
        }

        if ($new_balance < 0) {
            $pdo2->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถแก้ไขจำนวนได้ เนื่องจากยอดคงเหลือปัจจุบันไม่เพียงพอ']);
            exit;
        }

        // อัปเดตยอดคงเหลือ
        $stmtUpdMat = $pdo2->prepare("UPDATE mt_materials SET balance = :balance, updated_at = NOW() WHERE id = :id");
        $stmtUpdMat->execute([':balance' => $new_balance, ':id' => $material_id]);
    }

    // อัปเดตรายการ
    $sql = "UPDATE mt_transactions SET 
                quantity = :quantity,
                action_date = :action_date,
                receiver_name = :receiver_name,
                reference_dest = :reference_dest,
                note = :note
            WHERE id = :id";

    $stmtUpdateTx = $pdo2->prepare($sql);
    $stmtUpdateTx->execute([
        ':quantity' => $new_quantity,
        ':action_date' => isset($data['action_date']) ? $data['action_date'] : null,
        ':receiver_name' => isset($data['receiver_name']) ? $data['receiver_name'] : null,
        ':reference_dest' => isset($data['reference_dest']) ? $data['reference_dest'] : null,
        ':note' => isset($data['note']) ? $data['note'] : null,
        ':id' => $id
    ]);

    $pdo2->commit();
    echo json_encode(['status' => 'success', 'message' => 'บันทึกการแก้ไขเรียบร้อยแล้ว']);
} catch (PDOException $e) {
    $pdo2->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
