<?php
// backend/check_usernames.php
require_once "config.php";
header('Content-Type: application/json; charset=utf-8');

try {
    $stmt = $pdo3->query("SELECT HR_USERNAME FROM hr_person WHERE HR_USERNAME NOT REGEXP '^[0-9]{13}$' LIMIT 10");
    $nonNumeric = $stmt->fetchAll();

    $stmt = $pdo3->query("SELECT HR_USERNAME FROM hr_person WHERE HR_USERNAME REGEXP '^[0-9]{13}$' LIMIT 10");
    $numeric13 = $stmt->fetchAll();

    echo json_encode([
        'non_numeric_examples' => $nonNumeric,
        'numeric_13_examples' => $numeric13,
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
