<?php
require __DIR__ . '/../../config.php';

try {
    // Check if column exists
    $check = $pdo2->query("SHOW COLUMNS FROM kpi_definitions LIKE 'kpi_periodicity'");
    if ($check->rowCount() == 0) {
        // Add column
        // Periodicity: 'month', 'quarter', 'year'
        // Default to 'month'
        $sql = "ALTER TABLE kpi_definitions 
                ADD COLUMN kpi_periodicity ENUM('month', 'quarter', 'year') 
                NOT NULL DEFAULT 'month' 
                AFTER kpi_level";

        $pdo2->exec($sql);
        echo "Column 'kpi_periodicity' added successfully.";
    } else {
        echo "Column 'kpi_periodicity' already exists.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
