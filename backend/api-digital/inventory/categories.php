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
        $warehouse_id = isset($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : 0;
        
        $sql = "SELECT c.*, w.name as warehouse_name 
                FROM inv_categories c 
                LEFT JOIN inv_warehouses w ON c.warehouse_id = w.id ";
        $params = [];

        if ($warehouse_id > 0) {
            $sql .= "WHERE c.warehouse_id = ? ";
            $params[] = $warehouse_id;
        }
        $sql .= "ORDER BY w.code ASC, c.code ASC";

        $stmt = $pdo2->prepare($sql);
        $stmt->execute($params);
        $categories = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'data' => $categories]);
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        $warehouse_id = (int)($data['warehouse_id'] ?? 0);
        $code = trim($data['code'] ?? '');
        $name = trim($data['name'] ?? '');
        $active = isset($data['active']) ? (int)$data['active'] : 1;

        if (!$warehouse_id || !$code || !$name) {
            echo json_encode(['status' => 'error', 'message' => 'Warehouse, Code, and Name are required.']);
            exit;
        }

        $stmt = $pdo2->prepare("INSERT INTO inv_categories (warehouse_id, code, name, active) VALUES (?, ?, ?, ?)");
        $stmt->execute([$warehouse_id, $code, $name, $active]);
        echo json_encode(['status' => 'success', 'message' => 'Category created successfully.']);
    } elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = (int)($data['id'] ?? 0);
        $warehouse_id = (int)($data['warehouse_id'] ?? 0);
        $code = trim($data['code'] ?? '');
        $name = trim($data['name'] ?? '');
        $active = isset($data['active']) ? (int)$data['active'] : 1;

        if (!$id || !$warehouse_id || !$code || !$name) {
            echo json_encode(['status' => 'error', 'message' => 'ID, Warehouse, Code, and Name are required.']);
            exit;
        }

        $stmt = $pdo2->prepare("UPDATE inv_categories SET warehouse_id=?, code=?, name=?, active=? WHERE id=?");
        $stmt->execute([$warehouse_id, $code, $name, $active, $id]);
        echo json_encode(['status' => 'success', 'message' => 'Category updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
