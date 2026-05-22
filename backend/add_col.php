<?php
require 'config.php';
try {
    $pdo2->exec("ALTER TABLE revenue_targets ADD COLUMN target_per_month DECIMAL(10,2) NULL AFTER target_amount;");
    echo "Column target_per_month added successfully.";
} catch (Exception $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Column already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
