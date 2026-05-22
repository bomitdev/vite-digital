<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$material_id = isset($_GET['material_id']) ? intval($_GET['material_id']) : 0;
$action_type = isset($_GET['action_type']) ? trim($_GET['action_type']) : '';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

try {
    $where = [];
    $params = [];

    if ($material_id > 0) {
        $where[] = "t.material_id = :id";
        $params[':id'] = $material_id;
    }

    if ($action_type === 'IN' || $action_type === 'OUT') {
        $where[] = "t.action_type = :type";
        $params[':type'] = $action_type;
    }

    if ($start_date !== '' && $end_date !== '') {
        $where[] = "DATE(t.action_date) BETWEEN :start AND :end";
        $params[':start'] = $start_date;
        $params[':end'] = $end_date;
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "
        SELECT 
            t.id, 
            t.material_id, 
            m.code AS material_code, 
            m.name AS material_name, 
            m.unit,
            t.action_type, 
            t.quantity, 
            t.action_date, 
            t.user_profile_name, 
            t.receiver_name,
            t.reference_dest, 
            t.note
        FROM mt_transactions t
        LEFT JOIN mt_materials m ON t.material_id = m.id
        $whereClause
        ORDER BY t.action_date DESC, t.id DESC
    ";

    $stmt = $pdo2->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $transactions
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'DB Error: ' . $e->getMessage()
    ]);
}
