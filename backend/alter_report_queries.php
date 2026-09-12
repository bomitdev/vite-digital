<?php
require __DIR__ . '/config.php';
try {
    $sql = "ALTER TABLE report_queries ADD COLUMN parameters TEXT DEFAULT NULL";
    $pdo2->exec($sql);
    echo "Column 'parameters' added successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
