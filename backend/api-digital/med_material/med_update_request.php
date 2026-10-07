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

if (!isset($data->request_no) || empty($data->request_no)) {
    echo json_encode(['success' => false, 'message' => 'Missing request_no']);
    exit;
}

if (!isset($data->requester_name) || !isset($data->department)) {
    echo json_encode(['success' => false, 'message' => 'Missing requester info']);
    exit;
}

$items = [];
if (isset($data->items) && is_array($data->items)) {
    $items = $data->items;
}

if (empty($items)) {
    echo json_encode(['success' => false, 'message' => 'No items provided']);
    exit;
}

try {
    $pdo2->beginTransaction();

    // Check if the request is still pending
    $stmtCheckStatus = $pdo2->prepare("SELECT status FROM med_requests WHERE request_no = :request_no LIMIT 1");
    $stmtCheckStatus->execute([':request_no' => $data->request_no]);
    $requestRow = $stmtCheckStatus->fetch(PDO::FETCH_ASSOC);

    if (!$requestRow) {
        throw new Exception("Request not found");
    }

    if ($requestRow['status'] !== 'pending') {
        throw new Exception("Cannot edit a request that is already processed (Status: " . $requestRow['status'] . ")");
    }

    // Delete old items
    $stmtDelete = $pdo2->prepare("DELETE FROM med_requests WHERE request_no = :request_no");
    $stmtDelete->execute([':request_no' => $data->request_no]);

    $stmtCheck = $pdo2->prepare("SELECT id, balance FROM med_materials WHERE id = :id");

    $sqlInsert = "INSERT INTO med_requests (request_date, requester_name, department, material_id, quantity, status, request_no) 
            VALUES (CURDATE(), :requester_name, :department, :material_id, :quantity, 'pending', :request_no)";
    $stmtInsert = $pdo2->prepare($sqlInsert);

    foreach ($items as $item) {
        if (!isset($item->material_id) || !isset($item->quantity)) {
            throw new Exception("Invalid item format missing material_id or quantity");
        }

        // Verify material exists and has enough balance
        $stmtCheck->execute([':id' => $item->material_id]);
        $materialRow = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$materialRow) {
            throw new Exception("Material ID {$item->material_id} not found");
        }

        if ($item->quantity <= 0) {
            throw new Exception("Quantity must be greater than 0");
        }

        if ($item->quantity > $materialRow['balance']) {
            throw new Exception("Insufficient stock balance for material ID {$item->material_id}. Requested: {$item->quantity}, Available: {$materialRow['balance']}");
        }

        $stmtInsert->execute([
            ':requester_name' => $data->requester_name,
            ':department' => $data->department,
            ':material_id' => $item->material_id,
            ':quantity' => $item->quantity,
            ':request_no' => $data->request_no
        ]);
    }

    $pdo2->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Material request updated successfully'
    ]);
} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
