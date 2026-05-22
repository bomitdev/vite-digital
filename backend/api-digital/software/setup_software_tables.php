<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
require_once '../../config.php';

try {
    // 1. Create sw_software table
    $sql_1 = "
    CREATE TABLE IF NOT EXISTS sw_software (
        id INT AUTO_INCREMENT PRIMARY KEY,
        software_name VARCHAR(255) NOT NULL,
        version VARCHAR(100) NULL,
        developer VARCHAR(255) NULL,
        license_key VARCHAR(255) NULL,
        license_type VARCHAR(100) NULL,
        start_date DATE NULL,
        expiry_date DATE NULL,
        max_installations INT NULL COMMENT 'NULL means unlimited',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo2->exec($sql_1);
    echo "Table sw_software created or already exists.\n";

    // 2. Create sw_installations table
    $sql_2 = "
    CREATE TABLE IF NOT EXISTS sw_installations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        software_id INT NOT NULL,
        machine_name VARCHAR(255) NULL,
        user_name VARCHAR(255) NULL,
        install_date DATE NULL,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (software_id) REFERENCES sw_software(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo2->exec($sql_2);
    echo "Table sw_installations created or already exists.\n";

    echo "Database schema setup complete.\n";
} catch (PDOException $e) {
    die("Error creating tables: " . $e->getMessage() . "\n");
}
