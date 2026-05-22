<?php
// Attempt 4
require __DIR__ . '/../config.php';

try {
    // Check DB1 ($pdo1)
    echo "<h3>DB1 Tables matching 'hr%'</h3>";
    $stmt = $pdo1->query("SHOW TABLES LIKE '%hr%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo $table . "<br>";
    }

    // Check DB2 ($pdo2)
    echo "<h3>DB2 Tables matching 'hr%'</h3>";
    $stmt2 = $pdo2->query("SHOW TABLES LIKE '%hr%'");
    $tables2 = $stmt2->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables2 as $table) {
        echo $table . "<br>";
    }

    // Check DB3 ($pdo3)
    echo "<h3>DB3 Tables matching 'hr%'</h3>";
    $stmt3 = $pdo3->query("SHOW TABLES LIKE '%hr%'");
    $tables3 = $stmt3->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables3 as $table) {
        echo $table . "<br>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
