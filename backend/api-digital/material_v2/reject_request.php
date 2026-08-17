<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config.php';

if (!isset($pdo2)) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->request_no)) {
    echo json_encode(['success' => false, 'message' => 'Request No is required']);
    exit;
}

$requestNo = $data->request_no;
$isLegacy = strpos($requestNo, 'LEGACY-') === 0;
$adminNote = isset($data->admin_note) ? $data->admin_note : '';

try {
    if ($isLegacy) {
        $id = str_replace('LEGACY-', '', $requestNo);
        $stmt = $pdo2->prepare("UPDATE mt_requests SET status = 'rejected', admin_note = :note WHERE id = :id AND status = 'pending'");
        $stmt->execute([
            ':note' => $adminNote,
            ':id' => $id
        ]);
    } else {
        $stmt = $pdo2->prepare("UPDATE mt_requests SET status = 'rejected', admin_note = :note WHERE request_no = :request_no AND status = 'pending'");
        $stmt->execute([
            ':note' => $adminNote,
            ':request_no' => $requestNo
        ]);
    }

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Request rejected successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Request not found or not in pending status']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

