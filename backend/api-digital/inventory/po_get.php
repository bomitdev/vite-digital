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
    echo json_encode(['status' => 'error', 'message' => 'Invalid PO ID']);
    exit;
}

try {
    // Get Header
    $stmt = $pdo2->prepare("
        SELECT po.*, pr.pr_number, pr.warehouse_id
        FROM inv_purchase_orders po
        LEFT JOIN inv_purchase_requests pr ON po.pr_id = pr.id
        WHERE po.id = ?
    ");
    $stmt->execute([$id]);
    $po = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$po) {
        throw new Exception("PO not found");
    }
    
    // Get Items
    $stmt = $pdo2->prepare("
        SELECT poi.*, i.item_name, i.item_code, u.name as unit_name
        FROM inv_purchase_order_items poi
        LEFT JOIN inv_items i ON poi.item_id = i.id
        LEFT JOIN inv_units u ON i.unit_id = u.id
        WHERE poi.po_id = ?
    ");
    $stmt->execute([$id]);
    $po['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $po]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
