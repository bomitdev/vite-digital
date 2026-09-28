<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

try {
    $sql = "ALTER TABLE kpi_definitions ADD COLUMN IF NOT EXISTS is_locked TINYINT(1) DEFAULT 0";
    $pdo2->exec($sql);
    echo json_encode(['status' => 'success', 'message' => 'is_locked column added']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
