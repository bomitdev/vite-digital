<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';

// Secure Auth
$userData = authGuard();


if (!isset($pdo2)) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->requester_name) || !isset($data->department) || !isset($data->material_id) || !isset($data->quantity)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Optional check: Ensure the material actually exists
    $stmtCheck = $pdo2->prepare("SELECT id FROM mt_materials WHERE id = :id");
    $stmtCheck->execute([':id' => $data->material_id]);
    if ($stmtCheck->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Material not found']);
        exit;
    }

    $sql = "INSERT INTO mt_requests (request_date, requester_name, department, material_id, quantity, status) 
            VALUES (CURDATE(), :requester_name, :department, :material_id, :quantity, 'pending')";

    $stmt = $pdo2->prepare($sql);

    $stmt->execute([
        ':requester_name' => $data->requester_name,
        ':department' => $data->department,
        ':material_id' => $data->material_id,
        ':quantity' => $data->quantity
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Material request submitted successfully'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
