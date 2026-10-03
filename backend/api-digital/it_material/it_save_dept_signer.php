<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';

// Secure Auth
$userData = authGuard();


if (!isset($pdo2)) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['department_name']) || empty($data['requester_name'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $stmt = $pdo2->prepare("
        INSERT INTO mt_department_signers (department_name, requester_name, requester_position) 
        VALUES (:dept, :name, :pos) 
        ON DUPLICATE KEY UPDATE requester_name = :name2, requester_position = :pos2
    ");

    $stmt->execute([
        ':dept' => trim($data['department_name']),
        ':name' => trim($data['requester_name']),
        ':pos' => isset($data['requester_position']) ? trim($data['requester_position']) : '',
        ':name2' => trim($data['requester_name']),
        ':pos2' => isset($data['requester_position']) ? trim($data['requester_position']) : ''
    ]);

    echo json_encode(['success' => true, 'message' => 'Department signer saved successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
