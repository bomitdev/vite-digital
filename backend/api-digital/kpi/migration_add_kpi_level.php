<?php
require __DIR__ . '/../../config.php';

try {
    // Check if column exists
    $check = $pdo2->query("SHOW COLUMNS FROM kpi_definitions LIKE 'kpi_level'");
    if ($check->rowCount() == 0) {
        // Add column
        $sql = "ALTER TABLE kpi_definitions 
                ADD COLUMN kpi_level VARCHAR(50) DEFAULT NULL 
                AFTER kpi_condition"; // kpi_condition might not exist, let's put it after description or calculation_type

        // Let's check calculation_type existence first to be safe where to put it, 
        // strictly speaking position doesn't matter much but good for readability.
        // We just added calculation_type, so let's put it after that.

        $sql = "ALTER TABLE kpi_definitions 
                ADD COLUMN kpi_level VARCHAR(50) DEFAULT 'โรงพยาบาล' 
                AFTER calculation_type";

        $pdo2->exec($sql);
        echo "Column 'kpi_level' added successfully.";
    } else {
        echo "Column 'kpi_level' already exists.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
