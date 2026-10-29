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
        $include_inactive = isset($_GET['include_inactive']) ? (int)$_GET['include_inactive'] : 0;
        
        if ($user_id !== '' && !$is_admin) {
            $activeClause = $include_inactive ? "" : "AND w.active = 1";
            $stmt = $pdo2->prepare("
                SELECT w.*, uw.access_level 
                FROM inv_warehouses w
                JOIN inv_user_warehouses uw ON w.id = uw.warehouse_id
                WHERE uw.user_id = ? $activeClause
                ORDER BY w.code ASC
            ");
            $stmt->execute([$user_id]);
            $warehouses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $activeClause = $include_inactive ? "" : "WHERE w.active = 1";
            if ($is_admin) {
                $stmt = $pdo2->query("SELECT w.*, 'ADMIN' as access_level FROM inv_warehouses w ORDER BY w.code ASC");
            } else {
                $stmt = $pdo2->query("SELECT w.*, 'ADMIN' as access_level FROM inv_warehouses w $activeClause ORDER BY w.code ASC");
            }
            $warehouses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        echo json_encode(['status' => 'success', 'data' => $warehouses]);
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        $code = trim($data['code'] ?? '');
        $name = trim($data['name'] ?? '');
        $active = isset($data['active']) ? (int)$data['active'] : 1;
        $user_id = trim($data['user_id'] ?? '');

        if (!$code || !$name) {
            echo json_encode(['status' => 'error', 'message' => 'Code and Name are required.']);
            exit;
        }

        $stmt = $pdo2->prepare("INSERT INTO inv_warehouses (code, name, active) VALUES (?, ?, ?)");
        $stmt->execute([$code, $name, $active]);
        $warehouse_id = $pdo2->lastInsertId();

        // If user_id is provided, automatically grant MANAGER access to the creator in inv_user_warehouses
        if ($user_id && $warehouse_id) {
            $stmtAccess = $pdo2->prepare("
                INSERT INTO inv_user_warehouses (warehouse_id, user_id, access_level) 
                VALUES (?, ?, 'MANAGER')
                ON DUPLICATE KEY UPDATE access_level = 'MANAGER'
            ");
            $stmtAccess->execute([$warehouse_id, $user_id]);
        }

        echo json_encode(['status' => 'success', 'message' => 'Warehouse created successfully.', 'id' => $warehouse_id]);
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
