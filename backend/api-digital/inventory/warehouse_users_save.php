<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST is allowed.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? '';

try {
    if ($action === 'add') {
        $warehouse_id = (int)($data['warehouse_id'] ?? 0);
        $user_id = trim($data['user_id'] ?? '');
        $access_level = trim($data['access_level'] ?? 'STAFF');
        
        if (!$warehouse_id || !$user_id) {
            echo json_encode(['status' => 'error', 'message' => 'Warehouse ID and User ID are required.']);
            exit;
        }

        $stmt = $pdo2->prepare("INSERT IGNORE INTO inv_user_warehouses (warehouse_id, user_id, access_level) VALUES (?, ?, ?)");
        $stmt->execute([$warehouse_id, $user_id, $access_level]);
        echo json_encode(['status' => 'success', 'message' => 'User added successfully.']);
        
    } elseif ($action === 'remove') {
        $id = (int)($data['id'] ?? 0);
        
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'ID is required.']);
            exit;
        }

        $stmt = $pdo2->prepare("DELETE FROM inv_user_warehouses WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'User removed successfully.']);
        
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
