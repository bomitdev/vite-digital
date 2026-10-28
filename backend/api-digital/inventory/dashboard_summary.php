<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

try {
    $warehouse_id = isset($_GET['warehouse_id']) && $_GET['warehouse_id'] !== '' ? (int)$_GET['warehouse_id'] : null;
    $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';
    $is_admin = isset($_GET['is_admin']) ? (int)$_GET['is_admin'] : 0;

    // 1. Warehouses list based on permissions
    if ($user_id !== '' && !$is_admin) {
        $stmtWh = $pdo2->prepare("
            SELECT w.id, w.code, w.name, uw.access_level 
            FROM inv_warehouses w
            JOIN inv_user_warehouses uw ON w.id = uw.warehouse_id
            WHERE uw.user_id = ? AND w.active = 1
            ORDER BY w.code ASC
        ");
        $stmtWh->execute([$user_id]);
        $warehouses = $stmtWh->fetchAll(PDO::FETCH_ASSOC);

        // If warehouse_id not in permitted warehouses, pick the first permitted warehouse
        $permittedIds = array_map(function($w) { return (int)$w['id']; }, $warehouses);
        if ($warehouse_id !== null && !in_array($warehouse_id, $permittedIds)) {
            $warehouse_id = !empty($permittedIds) ? $permittedIds[0] : -1;
        } elseif ($warehouse_id === null && !empty($permittedIds)) {
            // Non-admin default to their first warehouse instead of seeing all warehouses
            $warehouse_id = $permittedIds[0];
        }
    } else {
        $stmtWh = $pdo2->query("SELECT id, code, name, 'ADMIN' as access_level FROM inv_warehouses WHERE active = 1 ORDER BY code ASC");
        $warehouses = $stmtWh->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. Metrics calculation
    $whBalWhere = $warehouse_id ? "WHERE b.warehouse_id = " . $warehouse_id : "";
    $whTxWhere = $warehouse_id ? "AND t.warehouse_id = " . $warehouse_id : "";

    // Total Stock Balance & Value
    $sqlBal = "
        SELECT 
            COALESCE(SUM(b.balance_qty), 0) as total_balance_qty,
            COALESCE(SUM(b.balance_qty * COALESCE(NULLIF(i.standard_price, 0), NULLIF(i.last_purchase_price, 0), 0)), 0) as total_value,
            COUNT(DISTINCT CASE WHEN b.balance_qty > 0 THEN b.item_id END) as active_items_count
        FROM inv_stock_balances b
        JOIN inv_items i ON b.item_id = i.id
        $whBalWhere
    ";
    $balRow = $pdo2->query($sqlBal)->fetch(PDO::FETCH_ASSOC);

    // Total IN and Total OUT from transactions
    $sqlTx = "
        SELECT 
            COALESCE(SUM(CASE WHEN t.transaction_type = 'IN' THEN t.qty_in ELSE 0 END), 0) as total_in_qty,
            COALESCE(SUM(CASE WHEN t.transaction_type = 'OUT' THEN t.qty_out ELSE 0 END), 0) as total_out_qty,
            COUNT(CASE WHEN t.transaction_type = 'IN' THEN 1 END) as in_tx_count,
            COUNT(CASE WHEN t.transaction_type = 'OUT' THEN 1 END) as out_tx_count
        FROM inv_stock_transactions t
        WHERE 1=1 $whTxWhere
    ";
    $txRow = $pdo2->query($sqlTx)->fetch(PDO::FETCH_ASSOC);

    // Total registered items
    $totalItems = (int)$pdo2->query("SELECT COUNT(*) FROM inv_items WHERE active = 1")->fetchColumn();

    // 3. Low stock items (on_hand <= minimum_stock)
    $whOnHandSub = $warehouse_id 
        ? "COALESCE((SELECT SUM(b.balance_qty) FROM inv_stock_balances b WHERE b.item_id = i.id AND b.warehouse_id = $warehouse_id), 0)"
        : "COALESCE((SELECT SUM(b.balance_qty) FROM inv_stock_balances b WHERE b.item_id = i.id), 0)";

    $sqlLow = "
        SELECT 
            i.id,
            i.item_code,
            i.item_name,
            COALESCE(c.name, 'ทั่วไป') as category_name,
            COALESCE(u.name, 'ชิ้น') as unit_name,
            COALESCE(i.minimum_stock, 5) as minimum_stock,
            COALESCE(i.reorder_point, 10) as reorder_point,
            COALESCE(NULLIF(i.standard_price, 0), NULLIF(i.last_purchase_price, 0), 0) as price,
            $whOnHandSub as on_hand
        FROM inv_items i
        LEFT JOIN inv_categories c ON i.category_id = c.id
        LEFT JOIN inv_units u ON i.unit_id = u.id
        WHERE i.active = 1
        HAVING on_hand <= minimum_stock
        ORDER BY (on_hand - minimum_stock) ASC, on_hand ASC
        LIMIT 25
    ";
    $stmtLow = $pdo2->query($sqlLow);
    $lowStockItems = [];
    while ($r = $stmtLow->fetch(PDO::FETCH_ASSOC)) {
        $needed = ($r['minimum_stock'] > $r['on_hand']) ? ($r['minimum_stock'] * 2 - $r['on_hand']) : 10;
        if ($needed <= 0) $needed = 1;

        $statusType = 'DANGER';
        $statusText = 'สินค้าหมด (0 ' . $r['unit_name'] . ')';
        if ($r['on_hand'] > 0) {
            $statusType = 'WARNING';
            $statusText = 'ต่ำกว่าเกณฑ์ขั้นต่ำ';
        }

        $lowStockItems[] = [
            'id' => (int)$r['id'],
            'item_code' => $r['item_code'],
            'item_name' => $r['item_name'],
            'category_name' => $r['category_name'],
            'unit_name' => $r['unit_name'],
            'minimum_stock' => (float)$r['minimum_stock'],
            'reorder_point' => (float)$r['reorder_point'],
            'price' => (float)$r['price'],
            'on_hand' => (float)$r['on_hand'],
            'suggested_qty' => (float)$needed,
            'status_type' => $statusType,
            'status_text' => $statusText
        ];
    }

    // 4. Monthly Trend Data (Last 12 Months)
    $sqlMonthly = "
        SELECT 
            DATE_FORMAT(t.transaction_date, '%Y-%m') as month_key,
            SUM(CASE WHEN t.transaction_type = 'IN' THEN t.qty_in ELSE 0 END) as qty_in,
            SUM(CASE WHEN t.transaction_type = 'OUT' THEN t.qty_out ELSE 0 END) as qty_out
        FROM inv_stock_transactions t
        WHERE t.transaction_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        $whTxWhere
        GROUP BY month_key
        ORDER BY month_key ASC
    ";
    $stmtMonthly = $pdo2->query($sqlMonthly);
    $monthlyRows = $stmtMonthly->fetchAll(PDO::FETCH_ASSOC);

    $thaiMonths = [
        '01' => 'ม.ค.', '02' => 'ก.พ.', '03' => 'มี.ค.', '04' => 'เม.ย.',
        '05' => 'พ.ค.', '06' => 'มิ.ย.', '07' => 'ก.ค.', '08' => 'ส.ค.',
        '09' => 'ก.ย.', '10' => 'ต.ค.', '11' => 'พ.ย.', '12' => 'ธ.ค.'
    ];

    $monthlyTrends = [];
    foreach ($monthlyRows as $m) {
        $parts = explode('-', $m['month_key']);
        $yearTh = isset($parts[0]) ? substr((string)((int)$parts[0] + 543), -2) : '';
        $monthName = isset($parts[1]) && isset($thaiMonths[$parts[1]]) ? $thaiMonths[$parts[1]] : $m['month_key'];
        $label = $monthName . ' ' . $yearTh;

        $monthlyTrends[] = [
            'month_key' => $m['month_key'],
            'label' => $label,
            'qty_in' => (float)$m['qty_in'],
            'qty_out' => (float)$m['qty_out']
        ];
    }

    // 5. Category Distribution
    $sqlCat = "
        SELECT 
            COALESCE(c.name, 'ทั่วไป') as category_name,
            COUNT(DISTINCT i.id) as item_count,
            COALESCE(SUM(b.balance_qty), 0) as total_qty,
            COALESCE(SUM(b.balance_qty * COALESCE(NULLIF(i.standard_price, 0), NULLIF(i.last_purchase_price, 0), 0)), 0) as total_value
        FROM inv_categories c
        LEFT JOIN inv_items i ON i.category_id = c.id AND i.active = 1
        LEFT JOIN inv_stock_balances b ON b.item_id = i.id $whBalWhere
        GROUP BY c.id, c.name
        ORDER BY total_qty DESC, total_value DESC
    ";
    $stmtCat = $pdo2->query($sqlCat);
    $categoryDistribution = [];
    while ($c = $stmtCat->fetch(PDO::FETCH_ASSOC)) {
        if ($c['item_count'] > 0 || $c['total_qty'] > 0) {
            $categoryDistribution[] = [
                'name' => $c['category_name'],
                'item_count' => (int)$c['item_count'],
                'total_qty' => (float)$c['total_qty'],
                'total_value' => (float)$c['total_value']
            ];
        }
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'metrics' => [
                'total_value' => (float)($balRow['total_value'] ?? 0),
                'total_balance_qty' => (float)($balRow['total_balance_qty'] ?? 0),
                'active_items_count' => (int)($balRow['active_items_count'] ?? 0),
                'total_items_count' => $totalItems,
                'total_in_qty' => (float)($txRow['total_in_qty'] ?? 0),
                'total_out_qty' => (float)($txRow['total_out_qty'] ?? 0),
                'in_tx_count' => (int)($txRow['in_tx_count'] ?? 0),
                'out_tx_count' => (int)($txRow['out_tx_count'] ?? 0),
                'low_stock_count' => count($lowStockItems)
            ],
            'low_stock_items' => $lowStockItems,
            'monthly_trends' => $monthlyTrends,
            'category_distribution' => $categoryDistribution,
            'warehouses' => $warehouses
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
