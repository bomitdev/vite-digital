<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

try {
    $stmt = $pdo2->prepare("SELECT DISTINCT name FROM assets WHERE name IS NOT NULL AND name != '' ORDER BY name ASC");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_COLUMN); // Fetch as flat array
    echo json_encode(['status' => 'success', 'data' => $data]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
