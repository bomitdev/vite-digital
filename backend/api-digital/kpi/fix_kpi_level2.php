<?php
require __DIR__ . '/../../config.php';
$stmt = $pdo2->prepare("UPDATE kpi_definitions SET kpi_level = 'KPI-Thip' WHERE kpi_level = 'KPI THIP, KPI-Thip'");
$stmt->execute();
echo json_encode(['status' => 'success', 'message' => 'Updated ' . $stmt->rowCount() . ' rows']);
