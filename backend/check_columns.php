<?php
// backend/check_columns.php
require_once "config.php";
header('Content-Type: application/json; charset=utf-8');

try {
    $stmt = $pdo3->query("SELECT * FROM hr_person LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(array_keys($row), JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
