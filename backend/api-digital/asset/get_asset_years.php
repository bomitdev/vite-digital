<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

try {
  
    $sql = "SELECT DISTINCT LEFT(SUBSTRING_INDEX(asset_code, '/', -1), 2) as year 
            FROM assets 
            WHERE asset_code LIKE '%/%' 
            AND LENGTH(SUBSTRING_INDEX(asset_code, '/', -1)) >= 2
            ORDER BY year DESC";

    $stmt = $pdo2->prepare($sql);
    $stmt->execute();
    $years = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode(['status' => 'success', 'data' => $years]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
