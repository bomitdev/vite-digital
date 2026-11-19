<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

try {
    $warehouse_id = isset($_GET['warehouse_id']) && $_GET['warehouse_id'] !== '' ? (int)$_GET['warehouse_id'] : 0;
    $status = trim($_GET['status'] ?? 'all');
    $search = trim($_GET['search'] ?? '');
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    $where = ["1=1"];
    $params = [];

    if ($warehouse_id > 0) {
        $where[] = "r.warehouse_id = ?";
        $params[] = $warehouse_id;
    }

    if (!empty($status) && $status !== 'all') {
        $where[] = "r.status = ?";
        $params[] = strtoupper($status);
    }

    if (!empty($search)) {
        $where[] = "(r.req_no LIKE ? OR r.requester_name LIKE ? OR r.department_name LIKE ? OR r.purpose LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    $whereClause = implode(" AND ", $where);

    // Count totals by status for badge counters
    $statsSql = "
        SELECT 
            COUNT(*) as total_count,
            SUM(CASE WHEN r.status = 'PENDING' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN r.status = 'APPROVED' THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN r.status = 'REJECTED' THEN 1 ELSE 0 END) as rejected_count
        FROM inv_requisitions r
        WHERE " . ($warehouse_id > 0 ? "r.warehouse_id = " . (int)$warehouse_id : "1=1");
    
    $statsStmt = $pdo2->query($statsSql);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: [
        'total_count' => 0, 'pending_count' => 0, 'approved_count' => 0, 'rejected_count' => 0
    ];

    $sql = "
        SELECT 
            r.*,
            w.name as warehouse_name,
            w.code as warehouse_code,
            (SELECT COUNT(*) FROM inv_requisition_items WHERE requisition_id = r.id) as item_count,
            (SELECT SUM(qty_requested) FROM inv_requisition_items WHERE requisition_id = r.id) as total_qty_requested,
            (SELECT SUM(qty_approved) FROM inv_requisition_items WHERE requisition_id = r.id) as total_qty_approved,
            COALESCE(
                NULLIF(iss.total_amount, 0),
                (
                    SELECT SUM(
                        (CASE WHEN r.status = 'APPROVED' AND ri.qty_approved > 0 THEN ri.qty_approved ELSE ri.qty_requested END) *
                        COALESCE(
                            NULLIF((SELECT l.unit_cost FROM inv_stock_lots l WHERE l.id = ri.lot_id LIMIT 1), 0),
                            NULLIF(it.ave_rate, 0),
                            NULLIF(it.standard_price, 0),
                            NULLIF(it.last_purchase_price, 0),
                            NULLIF((
                                SELECT l2.unit_cost
                                FROM inv_stock_lots l2
                                WHERE l2.item_id = ri.item_id AND l2.warehouse_id = r.warehouse_id AND l2.balance_qty > 0
                                ORDER BY CASE WHEN l2.expiry_date IS NULL THEN 1 ELSE 0 END, l2.expiry_date ASC, l2.id ASC
                                LIMIT 1
                            ), 0),
                            0
                        )
                    )
                    FROM inv_requisition_items ri
                    LEFT JOIN inv_items it ON it.id = ri.item_id
                    WHERE ri.requisition_id = r.id
                ),
                0
            ) as total_amount
        FROM inv_requisitions r
        LEFT JOIN inv_warehouses w ON r.warehouse_id = w.id
        LEFT JOIN inv_issues iss ON r.issue_id = iss.id
        WHERE $whereClause
        ORDER BY 
            CASE WHEN r.status = 'PENDING' THEN 0 ELSE 1 END,
            r.id DESC
        LIMIT $limit OFFSET $offset
    ";

    $stmt = $pdo2->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $rows,
        'stats' => $stats
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

