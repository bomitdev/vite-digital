<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

try {
    $sql = "SELECT po.*, pr.pr_number, pr.warehouse_id, w.name as warehouse_name
            FROM inv_purchase_orders po
            LEFT JOIN inv_purchase_requests pr ON po.pr_id = pr.id
            LEFT JOIN inv_warehouses w ON pr.warehouse_id = w.id
            WHERE po.status IN ('ORDERED', 'PARTIAL_RECEIVED')
            ORDER BY po.created_at DESC";
    
    $stmt = $pdo2->prepare($sql);
    $stmt->execute();
    $pos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $pos]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
