<?php
require_once '../../config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS revenue_statements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        target_id INT NOT NULL,
        month INT NOT NULL,
        statement_amount DECIMAL(15,2) NOT NULL,
        statement_date DATE DEFAULT NULL,
        remark TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo2->exec($sql);
    echo "Table 'revenue_statements' created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
