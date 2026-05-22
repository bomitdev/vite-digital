<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

try {
    // 1. Add price_per_unit to mt_materials
    $query1 = "ALTER TABLE `mt_materials` ADD `price_per_unit` DECIMAL(10,2) NOT NULL DEFAULT '0.00' AFTER `unit`";

    // 2. Add total_price to mt_transactions
    $query2 = "ALTER TABLE `mt_transactions` ADD `total_price` DECIMAL(10,2) NOT NULL DEFAULT '0.00' AFTER `quantity`";

    $success1 = false;
    $success2 = false;

    try {
        $pdo2->exec($query1);
        $success1 = true;
    } catch (PDOException $e) {
        $error = $e->getMessage();
        if (strpos($error, 'Duplicate column name') !== false) {
            $success1 = true; // Already exists
        } else {
            throw $e;
        }
    }

    try {
        $pdo2->exec($query2);
        $success2 = true;
    } catch (PDOException $e) {
        $error = $e->getMessage();
        if (strpos($error, 'Duplicate column name') !== false) {
            $success2 = true; // Already exists
        } else {
            throw $e;
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Database successfully updated to support prices.'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'DB Error: ' . $e->getMessage()
    ]);
}
