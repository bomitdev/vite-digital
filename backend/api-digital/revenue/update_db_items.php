<?php
require_once '../../config.php';

try {
    $pdo2->exec("ALTER TABLE revenue_results ADD COLUMN achieved_items DECIMAL(15,2) DEFAULT NULL AFTER month");
    echo "Added achieved_items column.\n";
} catch (PDOException $e) {
    echo "Column achieved_items might already exist: " . $e->getMessage() . "\n";
}
