<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once '../../config.php';

if (!isset($pdo2)) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['department_name'])) {
    echo json_encode(['success' => false, 'message' => 'Missing department name']);
    exit;
}

try {
    $stmt = $pdo2->prepare("DELETE FROM mt_department_signers WHERE department_name = :dept");
    $stmt->execute([':dept' => $data['department_name']]);

    echo json_encode(['success' => true, 'message' => 'Department signer deleted successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
