<?php
require 'config.php';

try {
    $stmt = $pdo2->prepare("DESCRIBE duties_it");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns in duties_it:\n";
    print_r($columns);

    // Test a dummy insert
    // $stmt = $pdo2->prepare("INSERT INTO duties_it(employees_id, date) VALUES (:employee_id, :date)");
    // $stmt->execute([':employee_id' => 1, ':date' => '2026-04-01']);
    // echo "Insert test successful.\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
