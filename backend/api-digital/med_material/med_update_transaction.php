<?php
require_once '../../config.php';
require_once '../../cors.php';
require_once __DIR__ . '/../../auth_utils.php';
require_once __DIR__ . '/med_log_helper.php';

$userData = authGuard();

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
    $stmtTx = $pdo2->prepare("SELECT material_id, action_type, quantity FROM med_transactions WHERE id = :id FOR UPDATE");
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
    $stmtMat = $pdo2->prepare("SELECT balance FROM med_materials WHERE id = :id FOR UPDATE");
    $stmtMat->execute([':id' => $material_id]);
    $mat = $stmtMat->fetch(PDO::FETCH_ASSOC);

    $current_balance = intval($mat['balance']);

    if ($new_quantity != $old_quantity) {
        $pdo2->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถแก้ไขจำนวนผ่านหน้านี้ได้ (กรุณายกเลิกและทำรายการใหม่เพื่อความถูกต้องของระบบ Lot)']);
        exit;
    }

    // อัปเดตรายการ
    $sql = "UPDATE med_transactions SET 
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

    $username = $userData['name'] ?? $userData['user'] ?? 'Unknown User';
    insertAdminLog($pdo2, $username, 'UPDATE_TRANSACTION', "แก้ไขรายละเอียดการทำรายการ ID: $id");

    echo json_encode(['status' => 'success', 'message' => 'บันทึกการแก้ไขเรียบร้อยแล้ว']);
} catch (PDOException $e) {
    $pdo2->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
