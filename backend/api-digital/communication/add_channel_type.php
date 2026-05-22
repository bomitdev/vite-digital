<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once '../../config.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['type_name']) || empty(trim($data['type_name']))) {
    echo json_encode(['success' => false, 'message' => 'Type name is required']);
    exit;
}

$typeName = trim($data['type_name']);

try {
    // Check if type already exists
    $checkSql = "SELECT id FROM sw_communication_channel_types WHERE type_name = :type_name";
    $checkStmt = $pdo2->prepare($checkSql);
    $checkStmt->execute([':type_name' => $typeName]);

    if ($checkStmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Channel type already exists']);
        exit;
    }

    $insertSql = "INSERT INTO sw_communication_channel_types (type_name) VALUES (:type_name)";
    $insertStmt = $pdo2->prepare($insertSql);
    $insertStmt->execute([':type_name' => $typeName]);

    echo json_encode([
        'success' => true,
        'message' => 'Channel type added successfully',
        'data' => [
            'id' => $pdo2->lastInsertId(),
            'type_name' => $typeName
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
