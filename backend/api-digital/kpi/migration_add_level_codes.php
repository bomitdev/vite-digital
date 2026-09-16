<?php
header("Content-Type: text/plain; charset=UTF-8");

require __DIR__ . '/../../config.php';

try {
    // Check if column already exists to prevent error on re-run
    $stmt = $pdo2->prepare("SHOW COLUMNS FROM kpi_definitions LIKE 'level_codes'");
    $stmt->execute();
    $exists = $stmt->fetch();

    if (!$exists) {
        $sql = "ALTER TABLE kpi_definitions ADD COLUMN level_codes TEXT NULL DEFAULT NULL";
        $pdo2->exec($sql);
        echo "Successfully added 'level_codes' column to 'kpi_definitions' table.\n";
    } else {
        echo "Column 'level_codes' already exists in 'kpi_definitions' table.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
