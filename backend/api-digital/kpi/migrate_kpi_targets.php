<?php
require __DIR__ . '/../../config.php';

try {
    $pdo2->beginTransaction();

    // 1. Create table kpi_targets if not exists
    $sqlCreate = "
        CREATE TABLE IF NOT EXISTS kpi_targets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            kpi_id INT NOT NULL,
            budget_year INT NOT NULL,
            target_value DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_kpi_year (kpi_id, budget_year)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo2->exec($sqlCreate);
    echo "Table kpi_targets created or already exists.\n";

    // 2. Migrate existing data from kpi_definitions
    $sqlMigrate = "
        INSERT IGNORE INTO kpi_targets (kpi_id, budget_year, target_value)
        SELECT id, COALESCE(fiscal_year, YEAR(CURDATE()) + 543), COALESCE(target_value, 0)
        FROM kpi_definitions
    ";
    $stmt = $pdo2->exec($sqlMigrate);
    echo "Migrated $stmt records into kpi_targets.\n";

    $pdo2->commit();
    echo "Migration completed successfully.\n";

} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    echo "Migration failed: " . $e->getMessage() . "\n";
}
