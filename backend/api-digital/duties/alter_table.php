<?php
require __DIR__ . '/../../config.php';

try {
    $sql = "ALTER TABLE duties_it ADD COLUMN rate_override INT NULL DEFAULT NULL AFTER date";
    $pdo2->exec($sql);
    echo "Successfully added rate_override column to duties_it table.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column rate_override already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
