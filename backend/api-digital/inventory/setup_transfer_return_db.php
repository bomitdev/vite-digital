<?php
require_once '../../config.php';

try {
    $pdo2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Transfer Tables
    $sql_transfers = "
    CREATE TABLE IF NOT EXISTS inv_transfers (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        transfer_no VARCHAR(50) NOT NULL UNIQUE,
        transfer_date DATETIME NOT NULL,
        from_warehouse_id INT(11) NOT NULL,
        to_warehouse_id INT(11) NOT NULL,
        user_name VARCHAR(100) DEFAULT NULL,
        remark TEXT DEFAULT NULL,
        status VARCHAR(20) DEFAULT 'COMPLETED',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (from_warehouse_id),
        INDEX (to_warehouse_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ";
    $pdo2->exec($sql_transfers);

    $sql_transfer_items = "
    CREATE TABLE IF NOT EXISTS inv_transfer_items (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        transfer_id INT(11) NOT NULL,
        item_id INT(11) NOT NULL,
        lot_id INT(11) DEFAULT NULL,
        qty DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (transfer_id),
        INDEX (item_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ";
    $pdo2->exec($sql_transfer_items);

    // 2. Return Tables
    $sql_returns = "
    CREATE TABLE IF NOT EXISTS inv_returns (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        return_no VARCHAR(50) NOT NULL UNIQUE,
        return_date DATETIME NOT NULL,
        warehouse_id INT(11) NOT NULL,
        return_from VARCHAR(150) NOT NULL,
        reason TEXT DEFAULT NULL,
        user_name VARCHAR(100) DEFAULT NULL,
        status VARCHAR(20) DEFAULT 'COMPLETED',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (warehouse_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ";
    $pdo2->exec($sql_returns);

    $sql_return_items = "
    CREATE TABLE IF NOT EXISTS inv_return_items (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        return_id INT(11) NOT NULL,
        item_id INT(11) NOT NULL,
        lot_number VARCHAR(50) DEFAULT NULL,
        expiry_date DATE DEFAULT NULL,
        qty DECIMAL(10,2) NOT NULL,
        unit_cost DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (return_id),
        INDEX (item_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ";
    $pdo2->exec($sql_return_items);

    echo "Transfer and Return tables created successfully.";
} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
}
?>
