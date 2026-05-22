<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . '/../../config.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['name'])) {
        throw new Exception("Name is required.");
    }

    $stmt = $pdo2->prepare("INSERT INTO asset_brands (brand_name) VALUES (:name)");
    $stmt->execute([':name' => $data['name']]);

    echo json_encode(['status' => 'success', 'message' => 'Brand added successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
