<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

try {
    $warehouse_id = isset($_GET['warehouse_id']) ? intval($_GET['warehouse_id']) : 0;
    
    $sql = "SELECT pr.*, w.name as warehouse_name,
            (SELECT COUNT(*) FROM inv_purchase_request_items WHERE pr_id = pr.id) as item_count
            FROM inv_purchase_requests pr
            LEFT JOIN inv_warehouses w ON pr.warehouse_id = w.id
            WHERE 1=1";
    
    $params = [];
    if ($warehouse_id > 0) {
        $sql .= " AND pr.warehouse_id = ?";
        $params[] = $warehouse_id;
    }
    
    $sql .= " ORDER BY pr.created_at DESC";
    
    $stmt = $pdo2->prepare($sql);
    $stmt->execute($params);
    $prs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $prs]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
