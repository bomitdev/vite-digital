<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require __DIR__ . '/../../config.php';

$data = json_decode(file_get_contents("php://input"));

if (
    !isset($data->name) ||
    !isset($data->code) ||
    !isset($data->class_id)
) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Incomplete data']);
    exit;
}

try {
    // Check for duplicates
    $stmt = $pdo2->prepare("SELECT id FROM asset_types WHERE code = ? AND class_id = ?");
    $stmt->execute([$data->code, $data->class_id]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Type code already exists in this class']);
        exit;
    }

    $sql = "INSERT INTO asset_types (class_id, code, name) VALUES (?, ?, ?)";
    $stmt = $pdo2->prepare($sql);
    $stmt->execute([$data->class_id, $data->code, $data->name]);
    $id = $pdo2->lastInsertId();

    echo json_encode(['status' => 'success', 'message' => 'Type saved', 'id' => $id]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
