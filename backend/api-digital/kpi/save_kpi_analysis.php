<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . '/../../config.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['kpi_id'])) {
        throw new Exception("KPI ID is required");
    }

    $kpiId = $data['kpi_id'];
    $analysis = isset($data['analysis']) ? $data['analysis'] : null;

    $stmt = $pdo2->prepare("UPDATE kpi_definitions SET analysis = ? WHERE id = ?");
    $stmt->execute([$analysis, $kpiId]);

    echo json_encode(['status' => 'success', 'message' => 'Analysis saved successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
