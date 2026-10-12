<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

try {
    // Select PRs that are DIR_APPROVED and NOT yet linked to any PO
    $sql = "SELECT pr.*, w.name as warehouse_name
            FROM inv_purchase_requests pr
            LEFT JOIN inv_warehouses w ON pr.warehouse_id = w.id
            WHERE pr.status = 'DIR_APPROVED'
            AND NOT EXISTS (
                SELECT 1 FROM inv_purchase_orders po WHERE po.pr_id = pr.id
            )
            ORDER BY pr.created_at DESC";
    
    $stmt = $pdo2->prepare($sql);
    $stmt->execute();
    $prs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $prs]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
