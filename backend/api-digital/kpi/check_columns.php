<?php
require __DIR__ . '/../../config.php';

try {
    $stmt = $pdo2->query("DESCRIBE kpi_definitions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($columns, JSON_PRETTY_PRINT);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
