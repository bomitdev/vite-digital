<?php
require_once '../../config.php';

try {
    // 1. ตารางเก็บข้อมูลทะเบียน IP Address
    $sql1 = "CREATE TABLE IF NOT EXISTS it_ip_addresses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        device_name VARCHAR(100),
        mac_address VARCHAR(50),
        device_type VARCHAR(50) DEFAULT 'PC',
        department VARCHAR(100),
        user_name VARCHAR(100),
        status VARCHAR(20) DEFAULT 'active' COMMENT 'active, inactive, reserved',
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY(ip_address)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo2->exec($sql1);

    echo "<h3>Tables created successfully</h3>";

} catch (PDOException $e) {
    echo "<h3>Database Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
