<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

try {
    $stmt = $pdo2->query("SELECT id, name FROM asset_acquisition_methods ORDER BY id ASC");
    $methods = $stmt->fetchAll();
    
    echo json_encode([
        'status' => 'success',
        'data' => $methods
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
