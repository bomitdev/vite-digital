<?php
require_once '../../config.php';
require_once '../../auth_utils.php';

// Verify authentication
$user = authGuard();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Target ID is required.']);
    exit;
}

try {
    $stmt = $pdo2->prepare("DELETE FROM revenue_targets WHERE id = ?");
    $stmt->execute([$data['id']]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Target deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Target not found.']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
