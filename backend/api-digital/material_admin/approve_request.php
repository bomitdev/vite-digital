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

if (!isset($data->id) && (!isset($data->approvals) || !is_array($data->approvals))) {
    echo json_encode(['success' => false, 'message' => 'Request ID or approvals array is required']);
    exit;
}

try {
    $pdo2->beginTransaction();

    $approvals = [];
    if (isset($data->approvals) && is_array($data->approvals)) {
        $approvals = $data->approvals;
    } else {
        // Fallback for single approval (legacy)
        $approvals[] = [
            'id' => $data->id,
            'approved_quantity' => $data->approved_quantity ?? null,
            'admin_note' => $data->admin_note ?? ''
        ];
    }

    foreach ($approvals as $approval) {
        $approval = (array) $approval; // Cast to array

        // Ensure 'id' is present and valid
        if (!isset($approval['id'])) {
            throw new Exception("Approval item missing 'id'");
        }
        $id = intval($approval['id']);

        // Fetch request details and material balance in one query
        $stmt = $pdo2->prepare("
            SELECT r.*, m.balance, m.id as material_id 
            FROM mt_admin_requests r
            JOIN mt_admin_materials m ON r.material_id = m.id
            WHERE r.id = :id FOR UPDATE
        ");
        $stmt->execute([':id' => $id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            throw new Exception("Request ID $id not found");
        }

        if ($request['status'] !== 'pending') {
            throw new Exception("Request ID $id is already processed (" . $request['status'] . ")");
        }

        $originalQty = (int)$request['quantity'];
        $approved_quantity = isset($approval['approved_quantity']) ? (int)$approval['approved_quantity'] : $originalQty;
        $admin_note = $approval['admin_note'] ?? 'Approved via system';

        if ($approved_quantity <= 0) {
            throw new Exception("Approved quantity for Request ID $id must be greater than zero");
        }

        if ($approved_quantity > $originalQty) {
            throw new Exception("Approved quantity for Request ID $id cannot exceed requested quantity ($originalQty)");
        }

        if ($approved_quantity > $request['balance']) {
            throw new Exception("Insufficient stock for Material ID " . $request['material_id'] . " for Request ID $id. Available: " . $request['balance'] . ", Requested: " . $approved_quantity);
        }

        // 2. Update Request status and approved quantity
        $stmtUpdate = $pdo2->prepare("
            UPDATE mt_admin_requests 
            SET status = 'approved', 
                quantity = :approved_quantity, 
                admin_note = :admin_note 
            WHERE id = :id
        ");
        $stmtUpdate->execute([
            ':approved_quantity' => $approved_quantity,
            ':admin_note' => $admin_note,
            ':id' => $id
        ]);

        // 3. Record transaction (Out)
        $stmtTx = $pdo2->prepare("
            INSERT INTO mt_admin_transactions (material_id, action_type, quantity, action_date, user_profile_name, receiver_name, reference_dest, note)
            VALUES (:material_id, 'OUT', :quantity, NOW(), :user, :receiver, :dest, :note)
        ");
        $stmtTx->execute([
            ':material_id' => $request['material_id'],
            ':quantity' => $approved_quantity,
            ':user' => $userData['user_profile_name'] ?? 'System Admin', // Use authenticated user if available
            ':receiver' => $request['requester_name'],
            ':dest' => $request['department'],
            ':note' => 'Approved request ID ' . $id . ($admin_note ? ' - ' . $admin_note : '')
        ]);

        // 4. Deduct from Material balance
        $stmtStock = $pdo2->prepare("
            UPDATE mt_admin_materials 
            SET balance = balance - :qty 
            WHERE id = :mid
        ");
        $stmtStock->execute([
            ':qty' => $approved_quantity,
            ':mid' => $request['material_id']
        ]);
    }

    $pdo2->commit();
    echo json_encode(['success' => true, 'message' => 'Requests approved successfully']);
} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
}
