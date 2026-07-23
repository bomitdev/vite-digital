<?php
require_once __DIR__ . '/config.php';

try {
    // Check if column exists first to avoid errors
    $checkSql = "SHOW COLUMNS FROM `10985_data_report` LIKE 'linked_report_id'";
    $stmt = $pdo3->query($checkSql);
    
    if ($stmt->rowCount() == 0) {
        $sql = "ALTER TABLE `10985_data_report` ADD COLUMN `linked_report_id` INT NULL AFTER `sql`";
        $pdo3->exec($sql);
        echo "Column linked_report_id added successfully.";
    } else {
        echo "Column already exists.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
