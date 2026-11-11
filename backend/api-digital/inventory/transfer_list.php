<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

try {
    $warehouse_id = isset($_GET['warehouse_id']) && $_GET['warehouse_id'] !== '' ? (int)$_GET['warehouse_id'] : 0;
    
    $sql = "SELECT t.*, 
            wf.name as from_warehouse, 
            wt.name as to_warehouse,
            (SELECT COUNT(*) FROM inv_transfer_items WHERE transfer_id = t.id) as item_count
            FROM inv_transfers t
            LEFT JOIN inv_warehouses wf ON t.from_warehouse_id = wf.id
            LEFT JOIN inv_warehouses wt ON t.to_warehouse_id = wt.id ";
    
    $params = [];
    if ($warehouse_id > 0) {
        $sql .= "WHERE (t.from_warehouse_id = ? OR t.to_warehouse_id = ?) ";
        $params[] = $warehouse_id;
        $params[] = $warehouse_id;
    }
    
    $sql .= "ORDER BY t.created_at DESC";
    
    $stmt = $pdo2->prepare($sql);
    $stmt->execute($params);
    $transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $transfers]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
