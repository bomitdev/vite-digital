<?php
require_once '../../config.php';

if (php_sapi_name() !== 'cli' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    die("This script can only be run via CLI or basic GET request for setup.\n");
}

try {
    // 1. Create mt_requests table
    $sql = "
    CREATE TABLE IF NOT EXISTS mt_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_date DATE NOT NULL,
        requester_name VARCHAR(255) NOT NULL,
        department VARCHAR(255) NOT NULL,
        material_id INT NOT NULL,
        quantity INT DEFAULT 1,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        admin_note TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (material_id) REFERENCES mt_materials(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo2->exec($sql);
    echo "Table mt_requests created or already exists.\n";

    echo "Material Request Database schema setup complete.\n";
} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage() . "\n");
}
