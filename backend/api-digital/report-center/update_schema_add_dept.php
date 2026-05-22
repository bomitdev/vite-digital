<?php
require '../config.php';

try {
    $sql = "ALTER TABLE report_queries ADD COLUMN department_id INT NULL DEFAULT NULL";
    $pdo2->exec($sql);
    echo "Column 'department_id' added successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
