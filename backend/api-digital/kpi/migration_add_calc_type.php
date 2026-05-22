<?php
require __DIR__ . '/../../config.php';

try {
    // Check if column exists
    $check = $pdo2->query("SHOW COLUMNS FROM kpi_definitions LIKE 'calculation_type'");
    if ($check->rowCount() == 0) {
        // Add column
        $sql = "ALTER TABLE kpi_definitions 
                ADD COLUMN calculation_type ENUM('percentage', 'multiplication') 
                NOT NULL DEFAULT 'percentage' 
                AFTER description";
        $pdo2->exec($sql);
        echo "Column 'calculation_type' added successfully.";
    } else {
        echo "Column 'calculation_type' already exists.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
