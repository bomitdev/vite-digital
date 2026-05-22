<?php
require 'config.php';

$tables = ['mt_materials', 'mt_requests', 'mt_transactions'];

foreach ($tables as $table) {
    echo "--- Table: $table ---\n";
    try {
        $stmt = $pdo2->query("DESCRIBE $table");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $col) {
            echo "{$col['Field']} - {$col['Type']} - {$col['Null']} - {$col['Key']} - {$col['Default']} - {$col['Extra']}\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}
