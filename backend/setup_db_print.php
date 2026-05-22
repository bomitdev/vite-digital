<?php
require_once 'config.php';

try {
    $pdo2->exec("
    CREATE TABLE IF NOT EXISTS `mt_global_settings` (
        `key_name` VARCHAR(100) PRIMARY KEY,
        `setting_value` TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS `mt_department_signers` (
        `department_name` VARCHAR(255) PRIMARY KEY,
        `requester_name` VARCHAR(255),
        `requester_position` VARCHAR(255)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- Insert default global settings if not exist
    INSERT IGNORE INTO mt_global_settings (key_name, setting_value) VALUES 
    ('payer_name', ''), 
    ('payer_position', 'เจ้าพนักงานพัสดุ'), 
    ('approver_name', ''), 
    ('approver_position', 'นักวิชาการพัสดุ');
    ");
    echo "Tables created successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
