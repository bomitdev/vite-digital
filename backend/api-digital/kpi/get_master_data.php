<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . '/../../config.php';

try {
    $data = [];

    // Levels
    $stmt = $pdo2->query("SELECT * FROM kpi_levels ORDER BY id");
    $data['levels'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Periodicities
    $stmt = $pdo2->query("SELECT * FROM kpi_periodicities ORDER BY id");
    $data['periodicities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Units
    $stmt = $pdo2->query("SELECT * FROM kpi_units ORDER BY id");
    $data['units'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculation Types
    $stmt = $pdo2->query("SELECT * FROM kpi_calculation_types ORDER BY id");
    $data['calculation_types'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Categories (Keep existing logic if any, or just fetch them too)
    // Assuming categories table exists
    $stmt = $pdo2->query("SELECT * FROM kpi_categories ORDER BY id");
    $data['categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);


    echo json_encode(['status' => 'success', 'data' => $data]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
