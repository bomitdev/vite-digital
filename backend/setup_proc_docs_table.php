<?php
require __DIR__ . '/config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS procurement_documents_data (
        id INT AUTO_INCREMENT PRIMARY KEY,
        bill_id INT NOT NULL,
        doc_date DATE DEFAULT NULL,
        to_person VARCHAR(255) DEFAULT 'ผู้ว่าราชการจังหวัดอำนาจเจริญ',
        reason TEXT DEFAULT NULL,
        budget DECIMAL(10,2) DEFAULT 0.00,
        delivery_days INT DEFAULT 15,
        committee TEXT DEFAULT NULL, 
        vendor_address TEXT DEFAULT NULL,
        vendor_tax_id VARCHAR(50) DEFAULT NULL,
        vendor_tel VARCHAR(50) DEFAULT NULL,
        buyer_name VARCHAR(100) DEFAULT NULL,
        buyer_position VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (bill_id) REFERENCES procurement_bills(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo2->exec($sql);
    echo "Table 'procurement_documents_data' created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
?>
