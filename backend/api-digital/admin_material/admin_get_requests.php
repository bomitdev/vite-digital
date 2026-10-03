<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once '../../config.php';

if (!isset($pdo2)) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Temporary migration: Add request_no if missing
    $checkColumn = $pdo2->query("SHOW COLUMNS FROM mt_admin_requests LIKE 'request_no'")->fetch();
    if (!$checkColumn) {
        $pdo2->exec("ALTER TABLE mt_admin_requests ADD COLUMN request_no VARCHAR(50) AFTER id");
    }

    $status = isset($_GET['status']) ? $_GET['status'] : 'all';

    $sql = "
        SELECT 
            r.*, 
            m.name AS material_name, 
            m.code AS material_code,
            m.balance AS current_balance
        FROM mt_admin_requests r
        JOIN mt_admin_materials m ON r.material_id = m.id
        WHERE 1=1
    ";

    $params = [];
    if ($status !== 'all') {
        $sql .= " AND r.status = :status";
        $params[':status'] = $status;
    }

    $sql .= " ORDER BY r.id DESC";

    $stmt = $pdo2->prepare($sql);
    $stmt->execute($params);
    $all_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Grouping logic in PHP
    $grouped = [];
    foreach ($all_rows as $row) {
        $key = !empty($row['request_no']) ? $row['request_no'] : ('ID-' . $row['id']);

        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'request_no' => $row['request_no'],
                'request_date' => $row['request_date'],
                'requester_name' => $row['requester_name'],
                'department' => $row['department'],
                'status' => $row['status'],
                'items' => []
            ];
        }

        $grouped[$key]['items'][] = [
            'id' => $row['id'],
            'material_id' => $row['material_id'],
            'material_name' => $row['material_name'],
            'material_code' => $row['material_code'],
            'quantity' => $row['quantity'],
            'admin_note' => $row['admin_note'],
            'current_balance' => $row['current_balance']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => array_values($grouped)
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
