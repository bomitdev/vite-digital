<?php
require_once __DIR__ . '/config.php';

try {
    // 1. inv_issues (เอกสารเบิกจ่าย)
    $pdo2->exec("
        CREATE TABLE IF NOT EXISTS inv_issues (
            id INT AUTO_INCREMENT PRIMARY KEY,
            issue_no VARCHAR(50) NOT NULL UNIQUE,
            issue_date DATETIME NOT NULL,
            warehouse_id INT NOT NULL,
            department_id INT,
            department_name VARCHAR(255),
            total_amount DECIMAL(14,2) DEFAULT 0.00,
            requester_name VARCHAR(255),
            issuer_id INT,
            issuer_name VARCHAR(255),
            remark TEXT,
            status VARCHAR(20) DEFAULT 'COMPLETED',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (warehouse_id) REFERENCES inv_warehouses(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. inv_issue_items (รายการเบิกจ่าย)
    $pdo2->exec("
        CREATE TABLE IF NOT EXISTS inv_issue_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            issue_id INT NOT NULL,
            item_id INT NOT NULL,
            lot_id INT, -- ต้องอ้างอิงจาก Lot ไหนถูกจ่ายออกไป
            qty DECIMAL(14,4) NOT NULL,
            unit_id INT NOT NULL,
            unit_cost DECIMAL(12,2) DEFAULT 0.00,
            total_price DECIMAL(14,2) DEFAULT 0.00,
            FOREIGN KEY (issue_id) REFERENCES inv_issues(id) ON DELETE CASCADE,
            FOREIGN KEY (item_id) REFERENCES inv_items(id) ON DELETE RESTRICT,
            FOREIGN KEY (lot_id) REFERENCES inv_stock_lots(id) ON DELETE RESTRICT,
            FOREIGN KEY (unit_id) REFERENCES inv_units(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo "Phase 3 schema created successfully.\n";

} catch (PDOException $e) {
    echo "Error creating schema: " . $e->getMessage() . "\n";
}
?>
