<?php
require __DIR__ . '/../../config.php';

try {
    $pdo2->beginTransaction();

    // Create Brands Table
    $sql = "CREATE TABLE IF NOT EXISTS asset_brands (
        brand_id INT AUTO_INCREMENT PRIMARY KEY,
        brand_name VARCHAR(100) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo2->exec($sql);

    // Seed Data
    $brands = [
        'Dell',
        'HP',
        'Acer',
        'Canon',
        'Brother',
        'Cisco',
        'Zebra',
        'Lenovo',
        'Asus',
        'Samsung',
        'D-Link',
        'Zyxel',
        '3-Com',
        'Toshiba',
        'TP-Link',
        'Compaq',
        'Epson',
        'IBM',
        'Mikrotik',
        'Ubiquiti',
        'AOC',
        'Ablerex',
        'APC',
        'DK',
        'LINKSYS',
        'Fortinete',
        'Ruijie',
        'Philips',
        'Fastid',
        'CDG',
        'Xiomi',
        'H3C',
        'Aruba',
        'Cyber Power',
        'เครื่องประกอบ',
        'อื่นๆ'
    ];

    $stmt = $pdo2->prepare("INSERT IGNORE INTO asset_brands (brand_name) VALUES (?)");
    foreach ($brands as $name) {
        $stmt->execute([$name]);
    }

    $pdo2->commit();
    echo "Asset Brands table created and seeded successfully.";
} catch (PDOException $e) {
    $pdo2->rollBack();
    echo "Error: " . $e->getMessage();
}
