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
    $whCond = $warehouse_id > 0 ? " AND pr.warehouse_id = ? " : "";

    // Select PRs that are DIR_APPROVED and NOT yet linked to any PO
    $sql = "SELECT pr.*, w.name as warehouse_name,
            (SELECT COUNT(*) FROM inv_purchase_request_items WHERE pr_id = pr.id) as item_count,
            (SELECT COALESCE(SUM(qty_requested * estimated_price), 0) FROM inv_purchase_request_items WHERE pr_id = pr.id) as total_estimated
            FROM inv_purchase_requests pr
            LEFT JOIN inv_warehouses w ON pr.warehouse_id = w.id
            WHERE pr.status = 'DIR_APPROVED'
            $whCond
            AND NOT EXISTS (
                SELECT 1 FROM inv_purchase_orders po WHERE po.pr_id = pr.id AND po.status != 'CANCELLED'
            )
            ORDER BY pr.created_at DESC";
    
    $stmt = $pdo2->prepare($sql);
    if ($warehouse_id > 0) {
        $stmt->execute([$warehouse_id]);
    } else {
        $stmt->execute();
    }
    $prs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $prs]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
