<?php
require __DIR__ . '/../../config.php';

try {
    $pdo2->beginTransaction();

    // Create Categories Table
    $sql = "CREATE TABLE IF NOT EXISTS asset_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(20) NOT NULL UNIQUE COMMENT 'รหัสหมวด',
        name VARCHAR(255) NOT NULL COMMENT 'ชื่อหมวด',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo2->exec($sql);

    // Seed Data
    $categories = [
        ['7440', 'เครื่องคอมพิวเตอร์'],
        ['7441', 'เครื่องพิมพ์'],
        ['7442', 'อุปกรณ์เครือข่าย'],
        ['7443', 'อุปกรณ์จัดเก็บข้อมูล']
    ];

    $stmt = $pdo2->prepare("INSERT IGNORE INTO asset_categories (code, name) VALUES (?, ?)");
    foreach ($categories as $cat) {
        $stmt->execute($cat);
    }

    $pdo2->commit();
    echo "Asset Categories table created and seeded successfully.";
} catch (PDOException $e) {
    $pdo2->rollBack();
    echo "Error: " . $e->getMessage();
}
