<?php
require_once '../../config.php';
require_once '../../cors.php';
require_once __DIR__ . '/inv_log_helper.php';

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

        $stmt = $pdo2->prepare("
            INSERT INTO inv_user_warehouses (warehouse_id, user_id, access_level) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE access_level = VALUES(access_level)
        ");
        $stmt->execute([$warehouse_id, $user_id, $access_level]);
        recordInvActivityLog($pdo2, [
            'warehouse_id' => $warehouse_id,
            'page_code' => 'settings',
            'page_name' => 'ตั้งค่าระบบ (Master Data)',
            'action' => 'GRANT_WAREHOUSE_USER',
            'target_type' => 'warehouse_user',
            'target_id' => (string)$user_id,
            'details' => "กำหนดสิทธิ์ผู้ใช้ ID {$user_id} ในคลัง #{$warehouse_id} ระดับ {$access_level}",
            'meta' => ['target_user_id' => $user_id, 'access_level' => $access_level],
        ], $pdo3 ?? null);
        echo json_encode(['status' => 'success', 'message' => 'User access saved successfully.']);
        
    } elseif ($action === 'remove') {
        $id = (int)($data['id'] ?? 0);
        
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'ID is required.']);
            exit;
        }

        $stRow = $pdo2->prepare("SELECT warehouse_id, user_id, access_level FROM inv_user_warehouses WHERE id = ?");
        $stRow->execute([$id]);
        $row = $stRow->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo2->prepare("DELETE FROM inv_user_warehouses WHERE id = ?");
        $stmt->execute([$id]);
        recordInvActivityLog($pdo2, [
            'warehouse_id' => !empty($row['warehouse_id']) ? (int)$row['warehouse_id'] : null,
            'page_code' => 'settings',
            'page_name' => 'ตั้งค่าระบบ (Master Data)',
            'action' => 'REVOKE_WAREHOUSE_USER',
            'target_type' => 'warehouse_user',
            'target_id' => (string)($row['user_id'] ?? $id),
            'details' => "ถอดสิทธิ์ผู้ใช้ ID " . ($row['user_id'] ?? $id) . " ออกจากคลัง #" . ($row['warehouse_id'] ?? '-'),
        ], $pdo3 ?? null);
        echo json_encode(['status' => 'success', 'message' => 'User removed successfully.']);
        
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
