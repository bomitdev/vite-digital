<?php
require_once __DIR__ . '/config.php';

try {
    // 1. inv_stock_balances (ยอดรวมระดับคลัง)
    $pdo2->exec("
        CREATE TABLE IF NOT EXISTS inv_stock_balances (
            id INT AUTO_INCREMENT PRIMARY KEY,
            warehouse_id INT NOT NULL,
            item_id INT NOT NULL,
            balance_qty DECIMAL(14,4) DEFAULT 0.0000,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY wh_item_unique (warehouse_id, item_id),
            FOREIGN KEY (warehouse_id) REFERENCES inv_warehouses(id) ON DELETE CASCADE,
            FOREIGN KEY (item_id) REFERENCES inv_items(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. inv_stock_lots (ยอดแยกล็อต)
    $pdo2->exec("
        CREATE TABLE IF NOT EXISTS inv_stock_lots (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_id INT NOT NULL,
            warehouse_id INT NOT NULL,
            lot_number VARCHAR(100),
            expiry_date DATE,
            unit_cost DECIMAL(12,2) DEFAULT 0.00,
            receive_qty DECIMAL(14,4) DEFAULT 0.0000,
            balance_qty DECIMAL(14,4) DEFAULT 0.0000,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (item_id) REFERENCES inv_items(id) ON DELETE CASCADE,
            FOREIGN KEY (warehouse_id) REFERENCES inv_warehouses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 3. inv_receipts (เอกสารรับเข้า)
    $pdo2->exec("
        CREATE TABLE IF NOT EXISTS inv_receipts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            receipt_no VARCHAR(50) NOT NULL UNIQUE,
            receipt_date DATETIME NOT NULL,
            warehouse_id INT NOT NULL,
            vendor_id INT,
            po_id INT,
            invoice_no VARCHAR(100),
            total_amount DECIMAL(14,2) DEFAULT 0.00,
            receiver_id INT,
            receiver_name VARCHAR(255),
            remark TEXT,
            status VARCHAR(20) DEFAULT 'COMPLETED',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (warehouse_id) REFERENCES inv_warehouses(id) ON DELETE RESTRICT,
            FOREIGN KEY (vendor_id) REFERENCES inv_vendors(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 4. inv_receipt_items (รายการในเอกสารรับเข้า)
    $pdo2->exec("
        CREATE TABLE IF NOT EXISTS inv_receipt_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            receipt_id INT NOT NULL,
            item_id INT NOT NULL,
            lot_number VARCHAR(100),
            expiry_date DATE,
            qty DECIMAL(14,4) NOT NULL,
            unit_id INT NOT NULL,
            unit_cost DECIMAL(12,2) DEFAULT 0.00,
            total_price DECIMAL(14,2) DEFAULT 0.00,
            FOREIGN KEY (receipt_id) REFERENCES inv_receipts(id) ON DELETE CASCADE,
            FOREIGN KEY (item_id) REFERENCES inv_items(id) ON DELETE RESTRICT,
            FOREIGN KEY (unit_id) REFERENCES inv_units(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 5. inv_stock_transactions (Transaction Log สำหรับทุกการเคลื่อนไหว)
    $pdo2->exec("
        CREATE TABLE IF NOT EXISTS inv_stock_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            warehouse_id INT NOT NULL,
            item_id INT NOT NULL,
            lot_id INT,
            document_type VARCHAR(20) NOT NULL, -- e.g., 'RECEIVE', 'ISSUE', 'TRANSFER'
            document_id INT,
            transaction_type VARCHAR(20) NOT NULL, -- 'IN', 'OUT'
            qty_in DECIMAL(14,4) DEFAULT 0.0000,
            qty_out DECIMAL(14,4) DEFAULT 0.0000,
            unit_cost DECIMAL(12,2) DEFAULT 0.00,
            balance_qty DECIMAL(14,4) DEFAULT 0.0000,
            transaction_date DATETIME NOT NULL,
            user_id INT,
            user_name VARCHAR(255),
            remark TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (warehouse_id) REFERENCES inv_warehouses(id) ON DELETE RESTRICT,
            FOREIGN KEY (item_id) REFERENCES inv_items(id) ON DELETE RESTRICT,
            FOREIGN KEY (lot_id) REFERENCES inv_stock_lots(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo "Phase 2 schema created successfully.\n";

} catch (PDOException $e) {
    echo "Error creating schema: " . $e->getMessage() . "\n";
}
?>
