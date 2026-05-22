<?php
require __DIR__ . '/../../config.php';

$tables = ['employees_opdcard', 'employees_claim'];
foreach ($tables as $table) {
    try {
        $sql = "ALTER TABLE $table ADD COLUMN rate_holiday_special INT NULL DEFAULT NULL AFTER rate_weekday";
        $pdo2->exec($sql);
        echo "Successfully added rate_holiday_special to $table.\n";
    } catch (PDOException $e) {
        echo "Error on $table (rate_holiday_special): " . $e->getMessage() . "\n";
    }

    try {
        $sql = "ALTER TABLE $table ADD COLUMN rate_weekday_special INT NULL DEFAULT NULL AFTER rate_holiday_special";
        $pdo2->exec($sql);
        echo "Successfully added rate_weekday_special to $table.\n";
    } catch (PDOException $e) {
        echo "Error on $table (rate_weekday_special): " . $e->getMessage() . "\n";
    }
}
