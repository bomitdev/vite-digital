<?php
require __DIR__ . '/config.php';

try {
    $sql = "ALTER TABLE procurement_bills ADD COLUMN approval_file_path VARCHAR(255) AFTER notes";
    $pdo2->exec($sql);
    echo "Column approval_file_path added successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
