<?php
require __DIR__ . '/../../config.php';

$tables = ['duties_opdcard', 'duties_claim'];
foreach ($tables as $table) {
    try {
        $sql = "ALTER TABLE $table ADD COLUMN rate_override INT NULL DEFAULT NULL AFTER date";
        $pdo2->exec($sql);
        echo "Successfully added rate_override to $table.\n";
    } catch (PDOException $e) {
        echo "Error on $table: " . $e->getMessage() . "\n";
    }
}
