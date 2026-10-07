<?php
require __DIR__ . '/config.php';
try {
    $pdo2->beginTransaction();

    $stmt = $pdo2->query("SELECT * FROM mt_admin_lots WHERE receive_date < '2026-01-01 00:00:00'");
    $lots = $stmt->fetchAll();

    $stmtInsertTx = $pdo2->prepare("
        INSERT INTO mt_admin_transactions (material_id, action_type, quantity, total_price, action_date, user_profile_name, reference_dest, note)
        VALUES (:mat_id, 'IN', :qty, :total, :dt, 'System Migration', 'System', 'Initial Balance Migration')
    ");

    foreach ($lots as $lot) {
        $stmtInsertTx->execute([
            ':mat_id' => $lot['material_id'],
            ':qty' => $lot['original_qty'],
            ':total' => $lot['original_qty'] * $lot['price_per_unit'],
            ':dt' => $lot['receive_date']
        ]);
    }

    $pdo2->commit();
    echo "Inserted migration transactions successfully.";
} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    echo "Error: " . $e->getMessage();
}
