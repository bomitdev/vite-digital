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

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id)) {
    echo json_encode(['success' => false, 'message' => 'Request ID is required']);
    exit;
}

$adminNote = isset($data->admin_note) ? $data->admin_note : '';

try {
    $stmt = $pdo2->prepare("UPDATE mt_requests SET status = 'rejected', admin_note = :note WHERE id = :id AND status = 'pending'");
    $stmt->execute([
        ':note' => $adminNote,
        ':id' => $data->id
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Request rejected successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Request not found or not in pending status']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
