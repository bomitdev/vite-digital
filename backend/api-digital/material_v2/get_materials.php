<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

// ใช้ $pdo2 สำหรับ digital
if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$low_stock = isset($_GET['low_stock']) ? true : false;

try {
    $where = [];
    $params = [];

    if ($id > 0) {
        $where[] = "id = :id";
        $params[':id'] = $id;
    }

    if ($search !== '') {
        $where[] = "(code LIKE :search OR name LIKE :search OR type LIKE :search)";
        $params[':search'] = "%$search%";
    }

    if ($low_stock) {
        $where[] = "balance <= min_alert";
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo2->prepare("SELECT * FROM mt_materials $whereClause ORDER BY name ASC");
    $stmt->execute($params);
    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $materials
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'DB Error: ' . $e->getMessage()
    ]);
}
