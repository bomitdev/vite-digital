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

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id)) {
    echo json_encode(['success' => false, 'message' => 'Request ID is required']);
    exit;
}

try {
    $pdo2->beginTransaction();

    // 1. Get request details
    $stmtReq = $pdo2->prepare("SELECT * FROM mt_requests WHERE id = :id FOR UPDATE");
    $stmtReq->execute([':id' => $data->id]);
    $request = $stmtReq->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        $pdo2->rollBack();
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit;
    }

    if ($request['status'] !== 'pending') {
        $pdo2->rollBack();
        echo json_encode(['success' => false, 'message' => 'Only pending requests can be approved']);
        exit;
    }

    $qty = (int)$request['quantity'];
    $matId = $request['material_id'];

    // 2. Check material balance
    $stmtMat = $pdo2->prepare("SELECT balance FROM mt_materials WHERE id = :id FOR UPDATE");
    $stmtMat->execute([':id' => $matId]);
    $material = $stmtMat->fetch(PDO::FETCH_ASSOC);

    if (!$material || $material['balance'] < $qty) {
        $pdo2->rollBack();
        echo json_encode(['success' => false, 'message' => 'Insufficient stock balance to approve this request']);
        exit;
    }

    $newBalance = $material['balance'] - $qty;

    // 3. Update request status
    $adminNote = isset($data->admin_note) ? $data->admin_note : 'Approved via system';
    $stmtUpdateReq = $pdo2->prepare("UPDATE mt_requests SET status = 'approved', admin_note = :note WHERE id = :id");
    $stmtUpdateReq->execute([':note' => $adminNote, ':id' => $data->id]);

    // 4. Record transaction (Out)
    $stmtTx = $pdo2->prepare("
        INSERT INTO mt_transactions (material_id, action_type, quantity, action_date, user_profile_name, receiver_name, reference_dest, note)
        VALUES (:material_id, 'OUT', :quantity, NOW(), :user, :receiver, :dest, :note)
    ");
    $stmtTx->execute([
        ':material_id' => $matId,
        ':quantity' => $qty,
        ':user' => 'System Admin', // Adjust if you have auth to map who approved it
        ':receiver' => $request['requester_name'],
        ':dest' => $request['department'],
        ':note' => 'Approved request ID ' . $data->id
    ]);

    // 5. Update material balance
    $stmtUpdateMat = $pdo2->prepare("UPDATE mt_materials SET balance = :balance WHERE id = :id");
    $stmtUpdateMat->execute([':balance' => $newBalance, ':id' => $matId]);

    $pdo2->commit();

    echo json_encode(['success' => true, 'message' => 'Request approved successfully']);
} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
}
