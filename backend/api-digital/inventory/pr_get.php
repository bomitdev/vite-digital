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
    echo json_encode(['status' => 'error', 'message' => 'Invalid PR ID']);
    exit;
}

try {
    // Get Header
    $stmt = $pdo2->prepare("
        SELECT pr.*, w.name as warehouse_name 
        FROM inv_purchase_requests pr
        LEFT JOIN inv_warehouses w ON pr.warehouse_id = w.id
        WHERE pr.id = ?
    ");
    $stmt->execute([$id]);
    $pr = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pr) {
        throw new Exception("PR not found");
    }
    
    // Get Items
    $stmt = $pdo2->prepare("
        SELECT pri.*, i.item_name as item_name, i.item_code as item_code, u.name as unit_name
        FROM inv_purchase_request_items pri
        LEFT JOIN inv_items i ON pri.item_id = i.id
        LEFT JOIN inv_units u ON i.unit_id = u.id
        WHERE pri.pr_id = ?
    ");
    $stmt->execute([$id]);
    $pr['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $pr]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
