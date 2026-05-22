<?php
require 'config.php';
try {
    echo "fingle_scan_day_time schema:\n";
    print_r($pdo3->query('DESCRIBE fingle_scan_day_time')->fetchAll(PDO::FETCH_ASSOC));

    echo "\nSample records from fingle_scan_day_time:\n";
    print_r($pdo3->query('SELECT * FROM fingle_scan_day_time LIMIT 2')->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
