<?php
require_once '../../config.php';
require_once '../../auth_utils.php';

$userData = authOptional();
if (!$userData) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;
$achieved_items = isset($input['achieved_items']) && $input['achieved_items'] !== '' ? $input['achieved_items'] : null;
$collected_amount = isset($input['collected_amount']) && $input['collected_amount'] !== '' ? $input['collected_amount'] : 0.00;
$remark = $input['remark'] ?? null;

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Result ID is required.']);
    exit;
}

try {
    $stmt = $pdo2->prepare("UPDATE revenue_results SET achieved_items = ?, collected_amount = ?, remark = ? WHERE id = ?");
    if ($stmt->execute([$achieved_items, $collected_amount, $remark, $id])) {
        echo json_encode(['status' => 'success', 'message' => 'Result updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update result.']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
