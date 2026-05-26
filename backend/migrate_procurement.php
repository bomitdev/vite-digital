<?php
require __DIR__ . '/config.php';

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS procurement_bills (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bill_number VARCHAR(100) NOT NULL,
        vendor_name VARCHAR(255) NOT NULL,
        amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        bill_date DATE NOT NULL,
        notes TEXT,
        file_path VARCHAR(255),
        status ENUM('Draft', 'Forwarded', 'Received') NOT NULL DEFAULT 'Draft',
        created_by VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        forwarded_by VARCHAR(100),
        forwarded_at DATETIME,
        received_by VARCHAR(100),
        received_at DATETIME
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo2->exec($sql);
    echo "Table procurement_bills created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
