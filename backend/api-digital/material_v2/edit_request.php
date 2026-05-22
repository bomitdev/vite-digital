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

if (!isset($data->id) || !isset($data->requester_name) || !isset($data->department) || !isset($data->material_id) || !isset($data->quantity)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $pdo2->beginTransaction();

    // Check request
    $stmtCheck = $pdo2->prepare("SELECT * FROM mt_requests WHERE id = :id FOR UPDATE");
    $stmtCheck->execute([':id' => $data->id]);
    $request = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        $pdo2->rollBack();
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit;
    }

    // Optional check: Ensure material exists
    $stmtMat = $pdo2->prepare("SELECT id FROM mt_materials WHERE id = :id");
    $stmtMat->execute([':id' => $data->material_id]);
    if ($stmtMat->rowCount() === 0) {
        $pdo2->rollBack();
        echo json_encode(['success' => false, 'message' => 'Material not found']);
        exit;
    }

    // Only allow editing if pending or rejected (don't allow if approved to avoid stock mismatch)
    // Actually, if the user really wants to edit an approved request, we would need to refund old qty and deduct new qty. 
    // It's safer to only allow edit on 'pending' or 'rejected'.
    if ($request['status'] === 'approved') {
        $pdo2->rollBack();
        echo json_encode(['success' => false, 'message' => 'Cannot edit approved request. Delete it to refund stock instead.']);
        exit;
    }

    // Update request
    $sql = "UPDATE mt_requests 
            SET requester_name = :requester_name, 
                department = :department, 
                material_id = :material_id, 
                quantity = :quantity,
                request_date = IFNULL(:request_date, request_date)
            WHERE id = :id";

    $stmt = $pdo2->prepare($sql);
    $stmt->execute([
        ':id' => $data->id,
        ':requester_name' => $data->requester_name,
        ':department' => $data->department,
        ':material_id' => $data->material_id,
        ':quantity' => $data->quantity,
        ':request_date' => isset($data->request_date) ? $data->request_date : null
    ]);

    $pdo2->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Material request updated successfully'
    ]);
} catch (PDOException $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
