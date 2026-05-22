<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

try {
    // 1. Create Table if not exists
    $pdo2->exec("CREATE TABLE IF NOT EXISTS asset_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 2. Seed Data
    $types = ['PC', 'Notebook', 'Printer', 'Server', 'Tablet', 'Monitor', 'Scanner', 'UPS', 'Projector'];
    $stmt = $pdo2->prepare("INSERT IGNORE INTO asset_types (name) VALUES (?)");
    foreach ($types as $t) {
        $stmt->execute([$t]);
    }

    echo "Asset Types table created and seeded.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
