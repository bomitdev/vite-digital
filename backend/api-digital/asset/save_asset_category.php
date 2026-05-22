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

    if (empty($data['code']) || empty($data['name'])) {
        throw new Exception("Code and Name are required.");
    }

    $stmt = $pdo2->prepare("INSERT INTO asset_categories (code, name) VALUES (:code, :name)");
    $stmt->execute([
        ':code' => $data['code'],
        ':name' => $data['name']
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Category added successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
