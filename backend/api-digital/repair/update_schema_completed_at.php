<?php
require __DIR__ . '/../../config.php';

try {
    $sql = "ALTER TABLE computer_repair_requests ADD COLUMN completed_at DATETIME NULL AFTER status";
    $pdo2->exec($sql);
    echo "Column 'completed_at' added successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
