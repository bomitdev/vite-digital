<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require '../../config.php';

try {
    $sql = "SELECT id, title, description, db_connection, created_at, sql_query, department_id FROM report_queries ORDER BY created_at DESC";
    $stmt = $pdo2->query($sql); // Always read metadata from pdo2 (Digital)
    $reports = $stmt->fetchAll();

    echo json_encode($reports);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
