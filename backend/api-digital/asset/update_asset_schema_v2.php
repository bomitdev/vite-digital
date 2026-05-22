<?php
require __DIR__ . '/../../config.php';

try {
    $pdo2->beginTransaction();

    // 1. Create asset_classes table (Level 2)
    $sql = "CREATE TABLE IF NOT EXISTS asset_classes (
        class_id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NOT NULL, /* Link to asset_category code or id? Let's use code if current category_id in FE is code, check FE. FE uses code. But better use ID if possible. Let's check asset_categories. It key is id. */
        code VARCHAR(20) NOT NULL,
        name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_class (category_id, code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo2->exec($sql);

    // 2. Update asset_types table (Level 3)
    // Check if columns exist first or just add ignore
    try {
        $pdo2->exec("ALTER TABLE asset_types ADD COLUMN class_id INT DEFAULT NULL");
    } catch (Exception $e) {
    }
    try {
        $pdo2->exec("ALTER TABLE asset_types ADD COLUMN code VARCHAR(20) DEFAULT '0000'");
    } catch (Exception $e) {
    }

    // 3. Seed Data
    // Ensure Category 7440 exists
    $stmt = $pdo2->prepare("SELECT id FROM asset_categories WHERE code = ?");
    $stmt->execute(['7440']);
    $catId = $stmt->fetchColumn();

    if (!$catId) {
        $pdo2->prepare("INSERT INTO asset_categories (code, name) VALUES (?, ?)")->execute(['7440', 'ครุภัณฑ์คอมพิวเตอร์']);
        $catId = $pdo2->lastInsertId();
    }

    // Seed Classes
    // 001 - เครื่องคอมพิวเตอร์
    $stmt = $pdo2->prepare("INSERT IGNORE INTO asset_classes (category_id, code, name) VALUES (?, ?, ?)");
    $stmt->execute([$catId, '001', 'เครื่องคอมพิวเตอร์']);
    $class001Id = $pdo2->lastInsertId();
    if (!$class001Id) { // if ignored
        $s = $pdo2->prepare("SELECT class_id FROM asset_classes WHERE category_id=? AND code=?");
        $s->execute([$catId, '001']);
        $class001Id = $s->fetchColumn();
    }

    // 006 - จอคอมพิวเตอร์
    $stmt->execute([$catId, '006', 'จอคอมพิวเตอร์']);

    // Seed Types for Class 001
    // 0001 - PC
    $stmtType = $pdo2->prepare("UPDATE asset_types SET class_id=?, code=? WHERE name=?");
    // Try update existing first
    $stmtType->execute([$class001Id, '0001', 'PC']);

    // Insert if not exists (upsert logic simple)
    $check = $pdo2->prepare("SELECT id FROM asset_types WHERE code = ? AND class_id = ?");
    $check->execute(['0001', $class001Id]);
    if (!$check->fetch()) {
        $pdo2->prepare("INSERT INTO asset_types (class_id, code, name) VALUES (?, ?, ?)")->execute([$class001Id, '0001', 'คอมพิวเตอร์ตั้งโต๊ะ (PC)']);
    }

    // 0002 - All In One
    $check->execute(['0002', $class001Id]);
    if (!$check->fetch()) {
        $pdo2->prepare("INSERT INTO asset_types (class_id, code, name) VALUES (?, ?, ?)")->execute([$class001Id, '0002', 'คอมพิวเตอร์ All In One']);
    }

    // 0003 - Notebook
    $check->execute(['0003', $class001Id]);
    if (!$check->fetch()) {
        $pdo2->prepare("INSERT INTO asset_types (class_id, code, name) VALUES (?, ?, ?)")->execute([$class001Id, '0003', 'คอมพิวเตอร์โน้ตบุ๊ก (Notebook)']);
    }

    $pdo2->commit();
    echo "Schema updated and seeded successfully.";
} catch (PDOException $e) {
    $pdo2->rollBack();
    echo "Error: " . $e->getMessage();
}
