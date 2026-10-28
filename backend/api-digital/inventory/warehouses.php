<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';
        $is_admin = isset($_GET['is_admin']) ? (int)$_GET['is_admin'] : 0;
        
        if ($user_id !== '' && !$is_admin) {
            $stmt = $pdo2->prepare("
                SELECT w.*, uw.access_level 
                FROM inv_warehouses w
                JOIN inv_user_warehouses uw ON w.id = uw.warehouse_id
                WHERE uw.user_id = ? AND w.active = 1
                ORDER BY w.code ASC
            ");
            $stmt->execute([$user_id]);
            $warehouses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo2->query("SELECT w.*, 'ADMIN' as access_level FROM inv_warehouses w ORDER BY w.code ASC");
            $warehouses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        echo json_encode(['status' => 'success', 'data' => $warehouses]);
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        $code = trim($data['code'] ?? '');
        $name = trim($data['name'] ?? '');
        $active = isset($data['active']) ? (int)$data['active'] : 1;

        if (!$code || !$name) {
            echo json_encode(['status' => 'error', 'message' => 'Code and Name are required.']);
            exit;
        }

        $stmt = $pdo2->prepare("INSERT INTO inv_warehouses (code, name, active) VALUES (?, ?, ?)");
        $stmt->execute([$code, $name, $active]);
        echo json_encode(['status' => 'success', 'message' => 'Warehouse created successfully.']);
    } elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = (int)($data['id'] ?? 0);
        $code = trim($data['code'] ?? '');
        $name = trim($data['name'] ?? '');
        $active = isset($data['active']) ? (int)$data['active'] : 1;

        if (!$id || !$code || !$name) {
            echo json_encode(['status' => 'error', 'message' => 'ID, Code, and Name are required.']);
            exit;
        }

        $stmt = $pdo2->prepare("UPDATE inv_warehouses SET code=?, name=?, active=? WHERE id=?");
        $stmt->execute([$code, $name, $active, $id]);
        echo json_encode(['status' => 'success', 'message' => 'Warehouse updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
