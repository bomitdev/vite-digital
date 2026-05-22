<?php
require __DIR__ . '/../../config.php';

try {
    echo "Checking if department_id column exists in report_queries...\n";

    // Check if column exists
    $checkSql = "SHOW COLUMNS FROM report_queries LIKE 'department_id'";
    $stmt = $pdo2->prepare($checkSql);
    $stmt->execute();
    $exists = $stmt->fetch();

    if (!$exists) {
        echo "Column not found. Adding department_id column...\n";
        $sql = "ALTER TABLE report_queries ADD COLUMN department_id INT NULL DEFAULT NULL";
        $pdo2->exec($sql);
        echo "SUCCESS: Column 'department_id' added successfully.\n";
    } else {
        echo "SKIPPED: Column 'department_id' already exists.\n";
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
