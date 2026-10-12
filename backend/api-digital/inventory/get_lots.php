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
    // Fetch lots with balance > 0, ordered by Expiry Date (FEFO)
    $stmt = $pdo2->prepare("
        SELECT id as lot_id, lot_number, expiry_date, unit_cost, balance_qty 
        FROM inv_stock_lots 
        WHERE item_id = ? AND warehouse_id = ? AND balance_qty > 0
        ORDER BY 
            CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END, 
            expiry_date ASC, 
            id ASC
    ");
    $stmt->execute([$item_id, $warehouse_id]);
    $lots = $stmt->fetchAll();

    echo json_encode(['status' => 'success', 'data' => $lots]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
