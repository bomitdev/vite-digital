<?php
require_once __DIR__ . '/../../config.php';

try {
    // Drop table if exists (optional, for development)
    // $pdo2->exec("DROP TABLE IF EXISTS it_loans");

    $sql = "CREATE TABLE IF NOT EXISTS it_loans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        asset_id INT NOT NULL,
        borrower_name VARCHAR(255) NOT NULL,
        department VARCHAR(255) NOT NULL,
        objective TEXT,
        borrow_date DATETIME NOT NULL,
        expected_return_date DATE NOT NULL,
        actual_return_date DATETIME DEFAULT NULL,
        status ENUM('pending', 'borrowed', 'returned', 'rejected') DEFAULT 'pending',
        admin_note TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo2->exec($sql);
    echo "Table 'it_loans' created successfully.\n";
} catch (PDOException $e) {
    die("ERROR: Could not create table. " . $e->getMessage());
}
