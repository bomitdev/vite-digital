<?php
require 'config.php';

try {
    // Add sql_query column
    $pdo2->exec("ALTER TABLE kpi_definitions ADD COLUMN sql_query TEXT DEFAULT NULL");
    echo "Added sql_query column.\n";
} catch (PDOException $e) {
    echo "sql_query column may already exist: " . $e->getMessage() . "\n";
}

try {
    // Add db_connection column
    $pdo2->exec("ALTER TABLE kpi_definitions ADD COLUMN db_connection INT DEFAULT 1");
    echo "Added db_connection column.\n";
} catch (PDOException $e) {
    echo "db_connection column may already exist: " . $e->getMessage() . "\n";
}

echo "Migration completed.\n";
