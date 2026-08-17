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

if (!isset($data->group_id) || !isset($data->requester_name) || !isset($data->department) || empty($data->items)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $pdo2->beginTransaction();

    // 1. Identify which rows to delete based on request_no or legacy group_id
    $requestNo = isset($data->request_no) ? $data->request_no : null;
    $groupId = $data->group_id;

    $legacyId = null;
    if (strpos($groupId, 'LEGACY-') === 0) {
        $legacyId = str_replace('LEGACY-', '', $groupId);
    }

    // Check status of existing requests
    if ($requestNo) {
        $stmtCheck = $pdo2->prepare("SELECT status FROM mt_requests WHERE request_no = :request_no FOR UPDATE");
        $stmtCheck->execute([':request_no' => $requestNo]);
    } else {
        $stmtCheck = $pdo2->prepare("SELECT status FROM mt_requests WHERE id = :id FOR UPDATE");
        $stmtCheck->execute([':id' => $legacyId]);
    }

    $existingRequests = $stmtCheck->fetchAll(PDO::FETCH_ASSOC);

    if (empty($existingRequests)) {
        $pdo2->rollBack();
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit;
    }

    // Only allow editing if pending or rejected
    foreach ($existingRequests as $req) {
        if ($req['status'] === 'approved') {
            $pdo2->rollBack();
            echo json_encode(['success' => false, 'message' => 'Cannot edit an approved request. Please delete and recreate.']);
            exit;
        }
    }

    // 2. Delete existing requests
    if ($requestNo) {
        $stmtDelete = $pdo2->prepare("DELETE FROM mt_requests WHERE request_no = :request_no AND status != 'approved'");
        $stmtDelete->execute([':request_no' => $requestNo]);
    } else {
        $stmtDelete = $pdo2->prepare("DELETE FROM mt_requests WHERE id = :id AND status != 'approved'");
        $stmtDelete->execute([':id' => $legacyId]);
        
        // Generate a new request_no for this legacy ticket since it's becoming a proper batch
        $dateStr = date('Ymd-His');
        $random = bin2hex(random_bytes(2));
        $requestNo = "REQ-{$dateStr}-{$random}";
    }

    // 3. Insert new items
    $stmtInsert = $pdo2->prepare("
        INSERT INTO mt_requests (request_no, material_id, requester_name, department, quantity, request_date, status) 
        VALUES (:request_no, :material_id, :requester_name, :department, :quantity, :request_date, 'pending')
    ");

    $requestDate = isset($data->request_date) ? $data->request_date : date('Y-m-d');

    foreach ($data->items as $item) {
        // Validate material exists
        $stmtMat = $pdo2->prepare("SELECT id FROM mt_materials WHERE id = :id");
        $stmtMat->execute([':id' => $item->material_id]);
        if ($stmtMat->rowCount() === 0) {
            $pdo2->rollBack();
            echo json_encode(['success' => false, 'message' => "Material ID {$item->material_id} not found"]);
            exit;
        }

        $stmtInsert->execute([
            ':request_no' => $requestNo,
            ':material_id' => $item->material_id,
            ':requester_name' => $data->requester_name,
            ':department' => $data->department,
            ':quantity' => $item->quantity,
            ':request_date' => $requestDate
        ]);
    }

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
