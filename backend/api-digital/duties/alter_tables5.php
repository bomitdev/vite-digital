<?php
require __DIR__ . '/../../config.php';

try {
    $sql = "ALTER TABLE duties ADD COLUMN is_special TINYINT(1) NOT NULL DEFAULT 0 AFTER rate_override";
    $pdo2->exec($sql);
    echo "Successfully added is_special to duties.\n";
} catch (PDOException $e) {
    echo "Error on duties (is_special): " . $e->getMessage() . "\n";
}
