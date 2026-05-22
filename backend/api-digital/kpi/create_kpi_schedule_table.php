<?php
require __DIR__ . '/../../config.php';

try {
    // Drop if exists to clean up (dev only, or use IF NOT EXISTS)
    // $pdo2->exec("DROP TABLE IF EXISTS kpi_schedule");

    $sql = "CREATE TABLE IF NOT EXISTS kpi_schedule (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fiscal_year INT NOT NULL,
        period_type VARCHAR(20) NOT NULL, -- 'month', 'quarter', 'half_year', 'year'
        period_number INT NOT NULL, -- 1-12 for month, 1-4 for quarter
        period_name VARCHAR(100),
        input_start_date DATE,
        input_end_date DATE,
        unlock_start_date DATE,
        unlock_end_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_period (fiscal_year, period_type, period_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo2->exec($sql);
    echo "Table 'kpi_schedule' created successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
