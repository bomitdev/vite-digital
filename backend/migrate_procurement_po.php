<?php
require __DIR__ . '/config.php';

try {
    $sql = "ALTER TABLE procurement_bills ADD COLUMN po_file_path VARCHAR(255) AFTER approval_file_path";
    $pdo2->exec($sql);
    echo "Column po_file_path added successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
