<?php
require_once '../../config.php';

try {
    // We are using $pdo2 (digital db) as requested by the user.

    // Table: revenue_targets
    $sqlTargets = "CREATE TABLE IF NOT EXISTS revenue_targets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        revenue_name VARCHAR(255) NOT NULL,
        fiscal_year INT NOT NULL,
        target_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        unit_price VARCHAR(255) DEFAULT NULL,
        responsible_person VARCHAR(255) DEFAULT NULL,
        responsible_unit VARCHAR(255) DEFAULT NULL,
        claim_program VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo2->exec($sqlTargets);
    echo "Table 'revenue_targets' created or already exists.\n";

    // Table: revenue_results
    $sqlResults = "CREATE TABLE IF NOT EXISTS revenue_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        target_id INT NOT NULL,
        month INT NOT NULL,
        achieved_items DECIMAL(15,2) DEFAULT NULL,
        collected_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        report_date DATE DEFAULT NULL,
        remark TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (target_id) REFERENCES revenue_targets(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo2->exec($sqlResults);
    echo "Table 'revenue_results' created or already exists.\n";

    echo "Database setup completed successfully.\n";
} catch (PDOException $e) {
    echo "Error setting up database: " . $e->getMessage() . "\n";
}
