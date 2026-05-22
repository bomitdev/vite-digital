<?php
require __DIR__ . '/../../config.php';

try {
    $sql = "ALTER TABLE kpi_definitions ADD COLUMN fiscal_year INT(4) DEFAULT NULL AFTER category_id";
    $pdo2->exec($sql);
    echo "Column 'fiscal_year' added successfully to kpi_definitions.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Column 'fiscal_year' already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
