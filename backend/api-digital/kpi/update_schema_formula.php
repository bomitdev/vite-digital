<?php
// Suppress warnings to avoid cluttering output
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

require __DIR__ . '/../../config.php';

try {
    $pdo2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $columnsToAdd = [
        'numerator_label' => "VARCHAR(255) DEFAULT NULL COMMENT 'Label for the numerator'",
        'denominator_label' => "VARCHAR(255) DEFAULT NULL COMMENT 'Label for the denominator'",
        'multiplier' => "DECIMAL(10,2) DEFAULT NULL COMMENT 'Multiplier value (e.g. 100, 1000, 100000)'"
    ];

    // Get current columns
    $stmt = $pdo2->query("DESCRIBE kpi_definitions");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($columnsToAdd as $col => $def) {
        if (!in_array($col, $existingColumns)) {
            // Column does not exist, add it
            $sql = "ALTER TABLE kpi_definitions ADD COLUMN $col $def";
            $pdo2->exec($sql);
            echo "Added column: $col\n";
        } else {
            echo "Column already exists: $col\n";
        }
    }

    echo "Schema update completed successfully.\n";
} catch (PDOException $e) {
    echo "Error updating schema: " . $e->getMessage() . "\n";
}
