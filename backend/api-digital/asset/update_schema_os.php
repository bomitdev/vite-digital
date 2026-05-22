<?php
require __DIR__ . '/../../config.php';

try {
    // Create asset_os table
    $sql = "CREATE TABLE IF NOT EXISTS asset_os (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $pdo2->exec($sql);
    echo "Table 'asset_os' created successfully.<br>";

    // Insert default values
    $defaults = [
        'Windows 10',
        'Windows 11',
        'Ubuntu 24.04',
        'iOS',
        'Android',
        'macOS'
    ];

    $stmt = $pdo2->prepare("INSERT IGNORE INTO asset_os (name) VALUES (?)");
    foreach ($defaults as $os) {
        $stmt->execute([$os]);
    }
    echo "Default OS values inserted.<br>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
