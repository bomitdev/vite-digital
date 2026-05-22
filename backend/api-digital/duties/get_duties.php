<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . '/../../config.php';

try {
    $month = $_GET['month'] ?? date('m'); // รับเดือน เช่น 04
    $year = $_GET['year'] ?? date('Y');    // รับปี เช่น 2025

    $stmt = $pdo2->prepare("
        SELECT d.date, e.name
        FROM duties_it d
        INNER JOIN employees_it e ON d.employee_id = e.id
        WHERE MONTH(d.date) = :month AND YEAR(d.date) = :year
        ORDER BY d.date ASC
    ");
    $stmt->execute([
        ':month' => $month,
        ':year' => $year
    ]);

    $duties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $duties
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error fetching duties: ' . $e->getMessage()
    ]);
}
