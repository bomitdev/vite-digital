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
        echo json_encode(['status' => 'error', 'message' => 'Name is required.']);
        exit();
    }

    $stmt = $pdo2->prepare("INSERT INTO asset_brands (brand_name) VALUES (:name)");
    $stmt->execute([':name' => $data['name']]);

    echo json_encode(['status' => 'success', 'message' => 'Brand added successfully']);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo json_encode(['status' => 'error', 'message' => 'Brand already exists.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
