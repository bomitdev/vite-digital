<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

// ใช้ $pdo2 สำหรับฐานข้อมูล digital
if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

try {
    // 1. ตารางสินค้าคงคลังหลัก
    $query1 = "
    CREATE TABLE IF NOT EXISTS `mt_materials` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `code` VARCHAR(50) NOT NULL COMMENT 'รหัสสินค้า',
        `name` VARCHAR(150) NOT NULL COMMENT 'ชื่ออุปกรณ์',
        `type` VARCHAR(100) NOT NULL COMMENT 'ประเภท เช่น RAM, SSD, เมาส์',
        `unit` VARCHAR(50) NOT NULL COMMENT 'หน่วยนับ',
        `min_alert` INT(11) DEFAULT 5 COMMENT 'แจ้งเตือนขั้นต่ำ',
        `balance` INT(11) DEFAULT 0 COMMENT 'จำนวนคงคลัง',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `idx_code` (`code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    // 2. ตารางประวัติรับเข้าจ่ายออก
    $query2 = "
    CREATE TABLE IF NOT EXISTS `mt_transactions` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `material_id` INT(11) NOT NULL,
        `action_type` ENUM('IN', 'OUT') NOT NULL COMMENT 'รับเข้า/จ่ายออก',
        `quantity` INT(11) NOT NULL COMMENT 'จำนวน',
        `action_date` DATETIME NOT NULL COMMENT 'วันที่ทำรายการ',
        `user_profile_name` VARCHAR(150) NOT NULL COMMENT 'ผู้รับผิดชอบ/อนุมัติ',
        `reference_dest` VARCHAR(150) NOT NULL COMMENT 'แหล่งที่มา/ผู้รับ',
        `note` TEXT COMMENT 'หมายเหตุ',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        FOREIGN KEY (`material_id`) REFERENCES mt_materials(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo2->exec($query1);
    $pdo2->exec($query2);

    echo json_encode([
        'status' => 'success',
        'message' => 'Tables mt_materials and mt_transactions created successfully in digital DB.'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'DB Error: ' . $e->getMessage()
    ]);
}
