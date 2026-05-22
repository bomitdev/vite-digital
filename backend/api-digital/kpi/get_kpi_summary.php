<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . '/../../config.php';

try {
    $sql = "SELECT 
              r.id, 
              t.kpi_name,
              t.target_percent,
              r.numerator,
              r.denominator,
              r.percent_result,
              r.operator,
              r.pass_status,
              r.year_thai,
              r.created_at
            FROM kpi_results r
            JOIN kpi_targets t ON r.kpi_id = t.id
            ORDER BY r.created_at DESC";
    $stmt = $pdo2->query($sql);
    $results = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'results' => $results
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
