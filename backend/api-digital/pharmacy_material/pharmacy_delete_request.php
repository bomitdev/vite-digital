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

if (!isset($data->request_no)) {
    echo json_encode(['success' => false, 'message' => 'Missing Request No field']);
    exit;
}

$requestNo = $data->request_no;
$isLegacy = strpos($requestNo, 'LEGACY-') === 0;

try {
    $pdo2->beginTransaction();

    if ($isLegacy) {
        $id = str_replace('LEGACY-', '', $requestNo);
        $stmt = $pdo2->prepare("SELECT * FROM pharmacy_requests WHERE id = :id FOR UPDATE");
        $stmt->execute([':id' => $id]);
    } else {
        $stmt = $pdo2->prepare("SELECT * FROM pharmacy_requests WHERE request_no = :request_no FOR UPDATE");
        $stmt->execute([':request_no' => $requestNo]);
    }

    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($requests)) {
        $pdo2->rollBack();
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit;
    }

    $refundedCount = 0;
    foreach ($requests as $request) {
        // 2. If approved, refund stock and remove transaction
        if ($request['status'] === 'approved') {
            $qty = (int)$request['quantity'];
            $matId = $request['material_id'];

            // Add back balance
            $stmtMat = $pdo2->prepare("UPDATE pharmacy_materials SET balance = balance + :qty WHERE id = :matId");
            $stmtMat->execute([':qty' => $qty, ':matId' => $matId]);

            // Remove the transaction OUT that was created (Match both legacy and new note styles including admin notes)
            $noteLike1 = 'Approved request ID ' . $request['id'] . '%';
            $noteLike2 = 'Approved request No: ' . $requestNo . '%';
            $stmtDelTx = $pdo2->prepare("DELETE FROM pharmacy_transactions WHERE material_id = :matId AND action_type = 'OUT' AND (note LIKE :note1 OR note LIKE :note2)");
            $stmtDelTx->execute([':matId' => $matId, ':note1' => $noteLike1, ':note2' => $noteLike2]);

            $refundedCount++;
        }

        // 3. Delete the request
        $stmtDel = $pdo2->prepare("DELETE FROM pharmacy_requests WHERE id = :id");
        $stmtDel->execute([':id' => $request['id']]);
    }

    $pdo2->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Material request deleted' . ($refundedCount > 0 ? " and $refundedCount items refunded" : '')
    ]);
} catch (PDOException $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
