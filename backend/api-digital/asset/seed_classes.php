<?php
require __DIR__ . '/../../config.php';

try {
    // 1. Create asset_classes table
    $pdo2->exec("CREATE TABLE IF NOT EXISTS asset_classes (
        class_id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NOT NULL, 
        code VARCHAR(20) NOT NULL,
        name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_class (category_id, code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "Table asset_classes checked/created.\n";

    // 2. Alter asset_types
    try {
        $pdo2->exec("ALTER TABLE asset_types ADD COLUMN class_id INT DEFAULT NULL");
        echo "Column class_id added to asset_types.\n";
    } catch (Exception $e) {
        echo "Column class_id likely exists.\n";
    }

    try {
        $pdo2->exec("ALTER TABLE asset_types ADD COLUMN code VARCHAR(20) DEFAULT '0000'");
        echo "Column code added to asset_types.\n";
    } catch (Exception $e) {
        echo "Column code likely exists.\n";
    }

    // 3. Seed Data
    $catCode = '7440';
    $stmt = $pdo2->prepare("SELECT id FROM asset_categories WHERE code = ?");
    $stmt->execute([$catCode]);
    $catId = $stmt->fetchColumn();

    if (!$catId) {
        $pdo2->prepare("INSERT INTO asset_categories (code, name) VALUES (?, ?)")->execute([$catCode, 'ครุภัณฑ์คอมพิวเตอร์']);
        $catId = $pdo2->lastInsertId();
        echo "Category 7440 created.\n";
    } else {
        echo "Category 7440 exists ($catId).\n";
    }

    // Seed Class 001
    $stmt = $pdo2->prepare("SELECT class_id FROM asset_classes WHERE category_id = ? AND code = ?");
    $stmt->execute([$catId, '001']);
    $class001Id = $stmt->fetchColumn();

    if (!$class001Id) {
        $pdo2->prepare("INSERT INTO asset_classes (category_id, code, name) VALUES (?, ?, ?)")->execute([$catId, '001', 'เครื่องคอมพิวเตอร์']);
        $class001Id = $pdo2->lastInsertId();
        echo "Class 001 created ($class001Id).\n";
    } else {
        echo "Class 001 exists ($class001Id).\n";
    }

    // Seed Class 006
    $stmt->execute([$catId, '006']);
    if (!$stmt->fetchColumn()) {
        $pdo2->prepare("INSERT INTO asset_classes (category_id, code, name) VALUES (?, ?, ?)")->execute([$catId, '006', 'จอคอมพิวเตอร์']);
        echo "Class 006 created.\n";
    }

    // Seed Types for Class 001
    $types = [
        ['0001', 'คอมพิวเตอร์ตั้งโต๊ะ (PC)'],
        ['0002', 'คอมพิวเตอร์ All In One'],
        ['0003', 'คอมพิวเตอร์โน้ตบุ๊ก (Notebook)']
    ];

    foreach ($types as $t) {
        $code = $t[0];
        $name = $t[1];

        $check = $pdo2->prepare("SELECT id FROM asset_types WHERE code = ? AND class_id = ?");
        $check->execute([$code, $class001Id]);
        if (!$check->fetch()) {
            $pdo2->prepare("INSERT INTO asset_types (class_id, code, name) VALUES (?, ?, ?)")->execute([$class001Id, $code, $name]);
            echo "Type $code created.\n";
        } else {
            echo "Type $code exists.\n";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
