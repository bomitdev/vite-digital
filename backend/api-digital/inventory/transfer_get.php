<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Transfer ID']);
    exit;
}

try {
    $stmt = $pdo2->prepare("
        SELECT t.*, wf.name as from_warehouse_name, wt.name as to_warehouse_name
        FROM inv_transfers t
        LEFT JOIN inv_warehouses wf ON t.from_warehouse_id = wf.id
        LEFT JOIN inv_warehouses wt ON t.to_warehouse_id = wt.id
        WHERE t.id = ?
    ");
    $stmt->execute([$id]);
    $transfer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transfer) {
        throw new Exception("Transfer not found");
    }
    
    // Get Items
    $stmt = $pdo2->prepare("
        SELECT ti.*, i.item_name, i.item_code, u.name as unit_name, l.lot_number
        FROM inv_transfer_items ti
        LEFT JOIN inv_items i ON ti.item_id = i.id
        LEFT JOIN inv_units u ON i.unit_id = u.id
        LEFT JOIN inv_stock_lots l ON ti.lot_id = l.id
        WHERE ti.transfer_id = ?
    ");
    $stmt->execute([$id]);
    $transfer['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $transfer]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
