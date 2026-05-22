<?php
require __DIR__ . '/../../config.php';

// No authGuard() here to allow public access

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $code = isset($_GET['code']) ? $_GET['code'] : '';

    if (empty($code)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Asset code is required']);
        exit;
    }

    $sql = "SELECT id, asset_code, name, brand, model, type, os, spec_cpu, spec_ram, spec_storage, 
                   location, responsible_person, purchase_date, warranty_expire_date, status, image_path 
            FROM assets 
            WHERE asset_code = :code LIMIT 1";
            
    $stmt = $pdo2->prepare($sql);
    $stmt->execute([':code' => $code]);
    $asset = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($asset) {
        echo json_encode(['status' => 'success', 'data' => $asset]);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Asset not found']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
