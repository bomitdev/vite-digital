<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

try {
    $pdo2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. inv_requisitions table
    $sql_reqs = "
    CREATE TABLE IF NOT EXISTS inv_requisitions (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        req_no VARCHAR(50) NOT NULL UNIQUE,
        warehouse_id INT(11) NOT NULL,
        request_date DATETIME NOT NULL,
        department_id INT(11) DEFAULT NULL,
        department_name VARCHAR(150) NOT NULL,
        requester_name VARCHAR(150) NOT NULL,
        requester_phone VARCHAR(50) DEFAULT NULL,
        purpose TEXT DEFAULT NULL,
        status VARCHAR(20) DEFAULT 'PENDING',
        approver_name VARCHAR(100) DEFAULT NULL,
        approved_date DATETIME DEFAULT NULL,
        admin_note TEXT DEFAULT NULL,
        issue_id INT(11) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_warehouse (warehouse_id),
        INDEX idx_status (status),
        INDEX idx_req_date (request_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo2->exec($sql_reqs);

    // 2. inv_requisition_items table
    $sql_req_items = "
    CREATE TABLE IF NOT EXISTS inv_requisition_items (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        requisition_id INT(11) NOT NULL,
        item_id INT(11) NOT NULL,
        item_code VARCHAR(50) DEFAULT NULL,
        item_name VARCHAR(255) NOT NULL,
        unit_name VARCHAR(50) DEFAULT NULL,
        qty_requested DECIMAL(10,2) NOT NULL,
        qty_approved DECIMAL(10,2) DEFAULT 0.00,
        lot_id INT(11) DEFAULT NULL,
        status VARCHAR(20) DEFAULT 'PENDING',
        item_note VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_requisition (requisition_id),
        INDEX idx_item (item_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo2->exec($sql_req_items);

    echo json_encode(['status' => 'success', 'message' => 'Requisition tables created successfully.']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

