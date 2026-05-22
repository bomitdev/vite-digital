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
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';

    $sql = "
        SELECT 
            r.*, 
            m.name AS material_name, 
            m.code AS material_code,
            m.balance AS current_balance
        FROM mt_requests r
        JOIN mt_materials m ON r.material_id = m.id
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
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $requests
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
