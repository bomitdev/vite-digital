<?php
require_once __DIR__ . '/config.php';
try {
    $stmt = $pdo2->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables in DB2:\n" . implode("\n", $tables);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
