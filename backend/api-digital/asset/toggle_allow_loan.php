<?php
header("Content-Type: application/json; charset=UTF-8");
require __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';

// Secure Auth
$userData = authGuard();

try {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['id']) || !isset($data['allow_loan'])) {
        throw new Exception("ID and allow_loan are required.");
    }

    $sql = "UPDATE assets SET allow_loan = :allow_loan WHERE id = :id";
    $stmt = $pdo2->prepare($sql);
    $stmt->execute([
        ':allow_loan' => $data['allow_loan'],
        ':id' => $data['id']
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Status updated successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
