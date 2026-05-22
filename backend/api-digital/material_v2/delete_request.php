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

if (!isset($data->id)) {
    echo json_encode(['success' => false, 'message' => 'Missing ID field']);
    exit;
}

try {
    $pdo2->beginTransaction();

    // 1. Get request to check status
    $stmt = $pdo2->prepare("SELECT * FROM mt_requests WHERE id = :id FOR UPDATE");
    $stmt->execute([':id' => $data->id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        $pdo2->rollBack();
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit;
    }

    // 2. If approved, refund stock and remove transaction
    if ($request['status'] === 'approved') {
        $qty = (int)$request['quantity'];
        $matId = $request['material_id'];

        // Add back balance
        $stmtMat = $pdo2->prepare("UPDATE mt_materials SET balance = balance + :qty WHERE id = :matId");
        $stmtMat->execute([':qty' => $qty, ':matId' => $matId]);

        // Remove the transaction OUT that was created
        $stmtDelTx = $pdo2->prepare("DELETE FROM mt_transactions WHERE material_id = :matId AND action_type = 'OUT' AND note = :note");
        $stmtDelTx->execute([':matId' => $matId, ':note' => 'Approved request ID ' . $request['id']]);
    }

    // 3. Delete the request
    $stmtDel = $pdo2->prepare("DELETE FROM mt_requests WHERE id = :id");
    $stmtDel->execute([':id' => $data->id]);

    $pdo2->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Material request deleted' . ($request['status'] === 'approved' ? ' and material stock refunded' : '')
    ]);
} catch (PDOException $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
