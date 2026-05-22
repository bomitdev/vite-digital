<?php
require __DIR__ . '/../../config.php';

try {
    // 1. Create asset_units table
    $sqlKey = "CREATE TABLE IF NOT EXISTS asset_units (
        unit_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo2->exec($sqlKey);
    echo "Table 'asset_units' created or already exists.<br>";

    // 2. Add columns to assets table
    // Check if 'size' exists
    $stmt = $pdo2->query("SHOW COLUMNS FROM assets LIKE 'size'");
    if ($stmt->rowCount() == 0) {
        $pdo2->exec("ALTER TABLE assets ADD COLUMN size VARCHAR(50) DEFAULT NULL AFTER model");
        echo "Column 'size' added to 'assets'.<br>";
    } else {
        echo "Column 'size' already exists.<br>";
    }

    // Check if 'unit' exists
    $stmt = $pdo2->query("SHOW COLUMNS FROM assets LIKE 'unit'");
    if ($stmt->rowCount() == 0) {
        $pdo2->exec("ALTER TABLE assets ADD COLUMN unit VARCHAR(50) DEFAULT NULL AFTER size");
        echo "Column 'unit' added to 'assets'.<br>";
    } else {
        echo "Column 'unit' already exists.<br>";
    }

    // 3. Seed initial units
    $initialUnits = ['Inch', 'Meter', 'KVA', 'Set', 'Pcs', 'เครื่อง', 'ชิ้น', 'ชุด'];
    $stmt = $pdo2->prepare("INSERT IGNORE INTO asset_units (name) VALUES (?)");
    foreach ($initialUnits as $unit) {
        $stmt->execute([$unit]);
    }
    echo "Initial units seeded.<br>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
