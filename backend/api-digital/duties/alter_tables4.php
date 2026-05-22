<?php
require __DIR__ . '/../../config.php';

$tables = ['duties_opdcard', 'duties_claim'];
foreach ($tables as $table) {
    try {
        $sql = "ALTER TABLE $table ADD COLUMN is_special TINYINT(1) NOT NULL DEFAULT 0 AFTER rate_override";
        $pdo2->exec($sql);
        echo "Successfully added is_special to $table.\n";
    } catch (PDOException $e) {
        echo "Error on $table (is_special): " . $e->getMessage() . "\n";
    }
}
