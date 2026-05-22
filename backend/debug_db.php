<?php
require 'config.php';

function listTables($pdo, $name)
{
    echo "<h2>Tables in $name (" . $pdo->query('select database()')->fetchColumn() . ")</h2>";
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            echo "$table<br>";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}

listTables($pdo1, "DB1");
listTables($pdo2, "DB2");
