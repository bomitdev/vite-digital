<?php
require __DIR__ . '/../../config.php';

try {
    $sql = "ALTER TABLE employees_it ADD COLUMN rate_holiday_special INT NULL DEFAULT NULL AFTER rate_weekday";
    $pdo2->exec($sql);
    echo "Successfully added rate_holiday_special to employees_it.\n";
} catch (PDOException $e) {
    echo "Error on employees_it (rate_holiday_special): " . $e->getMessage() . "\n";
}

try {
    $sql = "ALTER TABLE employees_it ADD COLUMN rate_weekday_special INT NULL DEFAULT NULL AFTER rate_holiday_special";
    $pdo2->exec($sql);
    echo "Successfully added rate_weekday_special to employees_it.\n";
} catch (PDOException $e) {
    echo "Error on employees_it (rate_weekday_special): " . $e->getMessage() . "\n";
}

try {
    $sql = "ALTER TABLE duties_it ADD COLUMN is_special TINYINT(1) NOT NULL DEFAULT 0 AFTER rate_override";
    $pdo2->exec($sql);
    echo "Successfully added is_special to duties_it.\n";
} catch (PDOException $e) {
    echo "Error on duties_it (is_special): " . $e->getMessage() . "\n";
}
