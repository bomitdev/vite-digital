<?php
require_once '../../config.php';

try {
    // 1. ตารางเก็บข้อมูลทะเบียน Server
    $sql1 = "CREATE TABLE IF NOT EXISTS it_servers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        server_name VARCHAR(150) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        os VARCHAR(100),
        cpu VARCHAR(100),
        ram VARCHAR(100),
        storage VARCHAR(100),
        role VARCHAR(150),
        location VARCHAR(150),
        status VARCHAR(50) DEFAULT 'active' COMMENT 'active, inactive, maintenance',
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo2->exec($sql1);

    echo "<h3>Table 'it_servers' created successfully</h3>";
} catch (PDOException $e) {
    echo "<h3>Database Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
