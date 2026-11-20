<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
$warehouse_id = isset($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : 0;

if (!$item_id || !$warehouse_id) {
    echo json_encode(['status' => 'error', 'message' => 'Item ID and Warehouse ID are required.']);
    exit;
}

try {
    // Fetch lots with balance > 0, ordered by Expiry Date (FEFO) then Receive Date (FIFO)
    $stmt = $pdo2->prepare("
        SELECT l.id as lot_id, l.lot_number, l.expiry_date, l.unit_cost, l.balance_qty 
        FROM inv_stock_lots l
        WHERE l.item_id = ? AND l.warehouse_id = ? AND l.balance_qty > 0
        ORDER BY 
            CASE WHEN l.expiry_date IS NULL THEN 1 ELSE 0 END, 
            l.expiry_date ASC, 
            COALESCE((SELECT MIN(t.transaction_date) FROM inv_stock_transactions t WHERE t.lot_id = l.id AND t.transaction_type = 'IN'), l.created_at) ASC,
            l.id ASC
    ");
    $stmt->execute([$item_id, $warehouse_id]);
    $lots = $stmt->fetchAll();

    echo json_encode(['status' => 'success', 'data' => $lots]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
