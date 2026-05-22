<?php
require 'config.php';
try {
    $tables = ['emp_work_time', 'emp_finger_scan', 'finger_scan', 'person_finger_scan', 'user_finger_scan'];
    foreach ($tables as $t) {
        echo "Table: $t\n";
        try {
            print_r($pdo1->query("DESCRIBE $t")->fetchAll(PDO::FETCH_ASSOC));
            echo "Sample:\n";
            print_r($pdo1->query("SELECT * FROM $t ORDER BY 1 DESC LIMIT 1")->fetchAll(PDO::FETCH_ASSOC));
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
        }
        echo "---------------\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
