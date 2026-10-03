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
    $id = intval($data->id);

    // Find request_no
    $stmtNo = $pdo2->prepare("SELECT request_no FROM mt_admin_requests WHERE id = :id");
    $stmtNo->execute([':id' => $id]);
    $rowNo = $stmtNo->fetch(PDO::FETCH_ASSOC);
    $request_no = $rowNo ? $rowNo['request_no'] : null;

    if ($request_no) {
        $stmt = $pdo2->prepare("UPDATE mt_admin_requests SET status = 'rejected', admin_note = :note WHERE request_no = :request_no AND status = 'pending'");
        $stmt->execute([
            ':note' => $adminNote,
            ':request_no' => $request_no
        ]);
    } else {
        $stmt = $pdo2->prepare("UPDATE mt_admin_requests SET status = 'rejected', admin_note = :note WHERE id = :id AND status = 'pending'");
        $stmt->execute([
            ':note' => $adminNote,
            ':id' => $id
        ]);
    }

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Requests rejected successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No pending requests found for this ID/Group']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
