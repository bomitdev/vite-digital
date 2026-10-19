<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

try {
    $sql = "SELECT 
                po.*, 
                pr.pr_number,
                COALESCE(po.vendor_name, v.vendor_name, 'ไม่ระบุ') as display_vendor_name,
                (SELECT COUNT(*) FROM inv_purchase_order_items WHERE po_id = po.id) as item_count,
                COALESCE(po.total_amount, (SELECT SUM(total_price) FROM inv_purchase_order_items WHERE po_id = po.id), 0) as total_cost
            FROM inv_purchase_orders po
            LEFT JOIN inv_purchase_requests pr ON po.pr_id = pr.id
            LEFT JOIN inv_vendors v ON po.vendor_id = v.id
            WHERE 1=1";
    
    $params = [];
    if (!empty($search)) {
        $sql .= " AND (po.po_number LIKE ? OR po.vendor_name LIKE ? OR po.memo_no LIKE ? OR po.purchaser_name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if (!empty($status)) {
        $sql .= " AND po.status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY po.po_date DESC, po.id DESC";
    
    $stmt = $pdo2->prepare($sql);
    $stmt->execute($params);
    $pos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $pos], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
