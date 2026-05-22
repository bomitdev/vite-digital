<?php
require_once '../../config.php';
require_once '../../auth_utils.php';

$userData = authOptional();
if (!$userData) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Result ID is required.']);
    exit;
}

try {
    $stmt = $pdo2->prepare("DELETE FROM revenue_results WHERE id = ?");
    if ($stmt->execute([$id])) {
        echo json_encode(['status' => 'success', 'message' => 'Result deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete result.']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
