<?php
require __DIR__ . '/../../config.php';
$stmt = $pdo2->prepare("UPDATE kpi_definitions SET kpi_level = 'KPI-Thip' WHERE kpi_level LIKE '%KPI THIP%'");
$stmt->execute();
echo json_encode(['status' => 'success', 'message' => 'Updated ' . $stmt->rowCount() . ' rows']);
