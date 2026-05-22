<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require __DIR__ . '/../../config.php';

$data = json_decode(file_get_contents("php://input"));

if (
    !isset($data->name) ||
    !isset($data->code) ||
    !isset($data->category_id)
) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Incomplete data']);
    exit;
}

try {
    // Check for duplicates
    $stmt = $pdo2->prepare("SELECT class_id FROM asset_classes WHERE code = ? AND category_id = ?");
    $stmt->execute([$data->code, $data->category_id]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Class code already exists in this category']);
        exit;
    }

    $sql = "INSERT INTO asset_classes (category_id, code, name) VALUES (?, ?, ?)";
    $stmt = $pdo2->prepare($sql);
    $stmt->execute([$data->category_id, $data->code, $data->name]);
    $id = $pdo2->lastInsertId();

    echo json_encode(['status' => 'success', 'message' => 'Class saved', 'id' => $id]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
