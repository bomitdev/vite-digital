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

if (!isset($data->request_no)) {
    echo json_encode(['success' => false, 'message' => 'Request No is required']);
    exit;
}

$requestNo = $data->request_no;
$isLegacy = strpos($requestNo, 'LEGACY-') === 0;

try {
    $pdo2->beginTransaction();

    if ($isLegacy) {
        $id = str_replace('LEGACY-', '', $requestNo);
        $stmtReq = $pdo2->prepare("SELECT * FROM mt_requests WHERE id = :id FOR UPDATE");
        $stmtReq->execute([':id' => $id]);
    } else {
        $stmtReq = $pdo2->prepare("SELECT * FROM mt_requests WHERE request_no = :request_no FOR UPDATE");
        $stmtReq->execute([':request_no' => $requestNo]);
    }
    
    $requests = $stmtReq->fetchAll(PDO::FETCH_ASSOC);

    if (empty($requests)) {
        $pdo2->rollBack();
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit;
    }

    $adminNote = isset($data->admin_note) ? $data->admin_note : 'Approved via system';
    $stmtMat = $pdo2->prepare("SELECT balance FROM mt_materials WHERE id = :id FOR UPDATE");
    $stmtUpdateReq = $pdo2->prepare("UPDATE mt_requests SET status = 'approved', admin_note = :note WHERE id = :id");
    $stmtTx = $pdo2->prepare("
        INSERT INTO mt_transactions (material_id, action_type, quantity, action_date, user_profile_name, receiver_name, reference_dest, note)
        VALUES (:material_id, 'OUT', :quantity, NOW(), :user, :receiver, :dest, :note)
    ");
    $stmtUpdateMat = $pdo2->prepare("UPDATE mt_materials SET balance = :balance WHERE id = :id");

    foreach ($requests as $request) {
        if ($request['status'] !== 'pending') {
            continue; // Skip already processed items
        }

        $qty = (int)$request['quantity'];
        $matId = $request['material_id'];

        $stmtMat->execute([':id' => $matId]);
        $material = $stmtMat->fetch(PDO::FETCH_ASSOC);

        if (!$material || $material['balance'] < $qty) {
            $pdo2->rollBack();
            echo json_encode(['success' => false, 'message' => 'Insufficient stock balance to approve material ID ' . $matId]);
            exit;
        }

        $newBalance = $material['balance'] - $qty;

        $stmtUpdateReq->execute([':note' => $adminNote, ':id' => $request['id']]);

        $stmtTx->execute([
            ':material_id' => $matId,
            ':quantity' => $qty,
            ':user' => (isset($userData['user']) ? $userData['user'] : 'System Admin'),
            ':receiver' => $request['requester_name'],
            ':dest' => $request['department'],
            ':note' => 'Approved request No: ' . $requestNo
        ]);

        $stmtUpdateMat->execute([':balance' => $newBalance, ':id' => $matId]);
    }

    $pdo2->commit();

    echo json_encode(['success' => true, 'message' => 'Request approved successfully']);
} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
}

