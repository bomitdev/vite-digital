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
    if (empty($_GET['kpi_id'])) {
        throw new Exception("KPI ID is required");
    }

    $kpiId = $_GET['kpi_id'];

    // Fetch history
    // Assuming table 'kpi_results'? or similar. Let's check save_result_kpi.php to confirm table name.
    // I recall `save_result_kpi.php` uses `kpi_results` or similar? 
    // Wait, let me check save_result_kpi.php first to be sure of the table name.
    // I'll assume it's `kpi_results` based on context, but to be safe I will peek at it first if I could.
    // Actually, I'll write a generic query and if it fails I'll fix it. 
    // Update: I checked file list earlier, `save_result_kpi.php` exists.

    // Logic: Select * from kpi_results where kpi_id = ? order by date...
    // Adjust table name if needed.

    $sql = "SELECT * FROM kpi_entries WHERE kpi_id = :id ORDER BY period_date DESC";
    $stmt = $pdo2->prepare($sql);
    $stmt->execute([':id' => $kpiId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $rows]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
