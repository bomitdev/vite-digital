<?php
require '../config.php';

try {
    // Use pdo3 for hosoffice
    $stmt = $pdo3->query("DESCRIBE com_repair");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($columns, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
