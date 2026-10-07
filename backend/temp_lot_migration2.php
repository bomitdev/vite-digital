<?php
require __DIR__ . '/config.php';
try {
    $pdo2->beginTransaction();

    $stmtInsertLot = $pdo2->prepare("
        INSERT INTO mt_admin_lots (material_id, price_per_unit, original_qty, remaining_qty, receive_date)
        VALUES (:material_id, :price, :qty1, :qty2, :receive_date)
    ");

    $stmt = $pdo2->query("SELECT id, balance, price_per_unit FROM mt_admin_materials WHERE balance > 0");
    $materials = $stmt->fetchAll();

    foreach ($materials as $mat) {
        $check = $pdo2->prepare("SELECT COUNT(*) FROM mt_admin_lots WHERE material_id = ?");
        $check->execute([$mat['id']]);
        if ($check->fetchColumn() == 0) {
            $stmtInsertLot->execute([
                ':material_id' => $mat['id'],
                ':price' => $mat['price_per_unit'],
                ':qty1' => $mat['balance'],
                ':qty2' => $mat['balance'],
                ':receive_date' => date('Y-m-d H:i:s', strtotime('-1 year'))
            ]);
        }
    }

    $pdo2->commit();
    echo "Migration completed successfully!";
} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    echo "Error: " . $e->getMessage();
}
