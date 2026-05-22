<?php
require __DIR__ . '/../../config.php';

try {
    $pdo2->beginTransaction();

    $sql = "CREATE TABLE IF NOT EXISTS computer_repair_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_no VARCHAR(20) NOT NULL UNIQUE COMMENT 'Format: R-YYYYMM-XXXX',
        requester_name VARCHAR(100) NOT NULL,
        department VARCHAR(100),
        asset_code VARCHAR(50) COMMENT 'Optional linkage to asset',
        issue_title VARCHAR(255) NOT NULL,
        issue_description TEXT,
        image_path VARCHAR(255),
        contact_tel VARCHAR(50),
        location VARCHAR(255),
        status VARCHAR(50) DEFAULT 'Pending' COMMENT 'Pending, In Progress, Wait for Part, Completed, Cancelled',
        technician_name VARCHAR(100),
        technician_comment TEXT,
        completed_at DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo2->exec($sql);
    $pdo2->commit();
    echo "Table 'computer_repair_requests' created successfully.";
} catch (PDOException $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    echo "Error: " . $e->getMessage();
}
