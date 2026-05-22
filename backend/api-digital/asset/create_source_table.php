<?php
require __DIR__ . '/../../config.php';

try {
    // 1. Create asset_sources table
    $sqlKey = "CREATE TABLE IF NOT EXISTS asset_sources (
        source_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo2->exec($sqlKey);
    echo "Table 'asset_sources' created or already exists.<br>";

    // 2. Add columns to assets table
    // Check if 'acquisition_method' exists
    $stmt = $pdo2->query("SHOW COLUMNS FROM assets LIKE 'acquisition_method'");
    if ($stmt->rowCount() == 0) {
        $pdo2->exec("ALTER TABLE assets ADD COLUMN acquisition_method VARCHAR(50) DEFAULT NULL AFTER unit");
        echo "Column 'acquisition_method' added to 'assets'.<br>";
    } else {
        echo "Column 'acquisition_method' already exists.<br>";
    }

    // Check if 'source' exists
    $stmt = $pdo2->query("SHOW COLUMNS FROM assets LIKE 'source'");
    if ($stmt->rowCount() == 0) {
        $pdo2->exec("ALTER TABLE assets ADD COLUMN source VARCHAR(100) DEFAULT NULL AFTER acquisition_method");
        echo "Column 'source' added to 'assets'.<br>";
    } else {
        echo "Column 'source' already exists.<br>";
    }

    // 3. Seed initial sources (Optional, based on user input example)
    $initialSources = ['สสจ.อำนาจเจริญ', 'Ubon computer', 'TTcom'];
    $stmt = $pdo2->prepare("INSERT IGNORE INTO asset_sources (name) VALUES (?)");
    foreach ($initialSources as $src) {
        $stmt->execute([$src]);
    }
    echo "Initial sources seeded.<br>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
