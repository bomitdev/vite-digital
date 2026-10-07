<?php
require __DIR__ . '/config.php';
try {
    $pdo2->beginTransaction();

    // 1. Create mt_admin_lots
    $pdo2->exec("
        CREATE TABLE IF NOT EXISTS mt_admin_lots (
            id INT AUTO_INCREMENT PRIMARY KEY,
            material_id INT NOT NULL,
            price_per_unit DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            original_qty INT NOT NULL DEFAULT 0,
            remaining_qty INT NOT NULL DEFAULT 0,
            receive_date DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (material_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 2. Create mt_admin_transaction_lots
    $pdo2->exec("
        CREATE TABLE IF NOT EXISTS mt_admin_transaction_lots (
            id INT AUTO_INCREMENT PRIMARY KEY,
            transaction_id INT NOT NULL,
            lot_id INT NOT NULL,
            quantity INT NOT NULL,
            price_per_unit DECIMAL(10,2) NOT NULL,
            INDEX (transaction_id),
            INDEX (lot_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 3. Migrate existing balances into a default lot
    // Get all materials with balance > 0
    $stmt = $pdo2->query("SELECT id, balance, price_per_unit FROM mt_admin_materials WHERE balance > 0");
    $materials = $stmt->fetchAll();

    $stmtInsertLot = $pdo2->prepare("
        INSERT INTO mt_admin_lots (material_id, price_per_unit, original_qty, remaining_qty, receive_date)
        VALUES (:material_id, :price, :qty, :qty, :receive_date)
    ");

    foreach ($materials as $mat) {
        // Check if a lot already exists to prevent duplicate migration on re-run
        $check = $pdo2->prepare("SELECT COUNT(*) FROM mt_admin_lots WHERE material_id = ?");
        $check->execute([$mat['id']]);
        if ($check->fetchColumn() == 0) {
            $stmtInsertLot->execute([
                ':material_id' => $mat['id'],
                ':price' => $mat['price_per_unit'],
                ':qty' => $mat['balance'],
                ':receive_date' => date('Y-m-d H:i:s', strtotime('-1 year')) // backdate the default lot
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
