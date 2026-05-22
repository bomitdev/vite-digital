<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id) || empty($data->id) || !isset($data->status) || empty(trim($data->status))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields (id, status)']);
    exit;
}

$allowed_statuses = ['pending', 'borrowed', 'returned', 'rejected'];
if (!in_array($data->status, $allowed_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    $pdo2->beginTransaction();

    // Check if the loan exists and get current status
    $stmt = $pdo2->prepare("SELECT status, asset_id FROM it_loans WHERE id = :id FOR UPDATE");
    $stmt->execute([':id' => $data->id]);
    $loan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$loan) {
        throw new Exception("Loan request not found");
    }

    $admin_note = isset($data->admin_note) ? trim($data->admin_note) : null;
    $actual_return_date = ($data->status === 'returned') ? date('Y-m-d H:i:s') : null;

    $updateSql = "UPDATE it_loans SET status = :status, admin_note = :admin_note";
    $updateParams = [
        ':status' => $data->status,
        ':admin_note' => $admin_note,
        ':id' => $data->id
    ];

    if ($data->status === 'returned' && $loan['status'] !== 'returned') {
        $updateSql .= ", actual_return_date = :actual_return_date";
        $updateParams[':actual_return_date'] = $actual_return_date;

        // When returned, we might want to update the asset status back to 'สำรอง' if needed.
        // For now, depending on the asset management flow, the status could be manual.
        // Option to add: UPDATE assets SET status = 'สำรอง' WHERE id = :asset_id
    }

    $updateSql .= " WHERE id = :id";

    $updateStmt = $pdo2->prepare($updateSql);
    $updateStmt->execute($updateParams);

    // If changing to 'borrowed', we might update the asset status to 'Borrowed'.
    if ($data->status === 'borrowed' && $loan['status'] !== 'borrowed') {
        $stmtAsset = $pdo2->prepare("UPDATE assets SET status = 'Borrowed' WHERE id = :asset_id");
        $stmtAsset->execute([':asset_id' => $loan['asset_id']]);
    } else if ($data->status === 'returned' && $loan['status'] === 'borrowed') {
        $stmtAsset = $pdo2->prepare("UPDATE assets SET status = 'Spare' WHERE id = :asset_id");
        $stmtAsset->execute([':asset_id' => $loan['asset_id']]);
    }

    $pdo2->commit();

    echo json_encode(['success' => true, 'message' => 'Loan status updated successfully']);
} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
