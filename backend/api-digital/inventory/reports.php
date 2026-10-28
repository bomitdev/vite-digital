<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$type = isset($_GET['type']) ? trim($_GET['type']) : 'summary';
$warehouse_id = isset($_GET['warehouse_id']) && $_GET['warehouse_id'] !== '' ? (int)$_GET['warehouse_id'] : null;
$category_id = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null;
$vendor_id = isset($_GET['vendor_id']) && $_GET['vendor_id'] !== '' ? (int)$_GET['vendor_id'] : null;
$start_date = isset($_GET['start_date']) && $_GET['start_date'] !== '' ? $_GET['start_date'] . ' 00:00:00' : null;
$end_date = isset($_GET['end_date']) && $_GET['end_date'] !== '' ? $_GET['end_date'] . ' 23:59:59' : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$days = isset($_GET['days']) ? (int)$_GET['days'] : 60;

try {
    switch ($type) {
        case 'summary':
            handleSummary($pdo2, $warehouse_id);
            break;
        case 'balance':
            handleBalance($pdo2, $warehouse_id, $category_id, $status, $search);
            break;
        case 'receipts':
            handleReceipts($pdo2, $warehouse_id, $vendor_id, $start_date, $end_date, $search);
            break;
        case 'issues':
            handleIssues($pdo2, $warehouse_id, $start_date, $end_date, $search);
            break;
        case 'movement':
            handleMovement($pdo2, $warehouse_id, $category_id, $start_date, $end_date, $search);
            break;
        case 'expiring':
            handleExpiring($pdo2, $warehouse_id, $days, $search);
            break;
        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid report type']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

/**
 * 1. Overview Summary Cards
 */
function handleSummary($pdo, $warehouse_id) {
    $whBalWhere = $warehouse_id ? "WHERE b.warehouse_id = " . (int)$warehouse_id : "";
    $whTxWhere = $warehouse_id ? "AND t.warehouse_id = " . (int)$warehouse_id : "";
    $whLotWhere = $warehouse_id ? "AND l.warehouse_id = " . (int)$warehouse_id : "";

    // 1. Stock Balance & Value
    $sqlBal = "
        SELECT 
            COALESCE(SUM(b.balance_qty), 0) as total_balance_qty,
            COALESCE(SUM(b.balance_qty * COALESCE(NULLIF(i.standard_price, 0), NULLIF(i.last_purchase_price, 0), 0)), 0) as total_value,
            COUNT(DISTINCT CASE WHEN b.balance_qty > 0 THEN b.item_id END) as active_items_count
        FROM inv_stock_balances b
        JOIN inv_items i ON b.item_id = i.id
        $whBalWhere
    ";
    $bal = $pdo->query($sqlBal)->fetch(PDO::FETCH_ASSOC);

    // 2. Receipts & Issues this month
    $firstDayThisMonth = date('Y-m-01 00:00:00');
    $lastDayThisMonth = date('Y-m-t 23:59:59');

    $sqlMonth = "
        SELECT
            COALESCE(SUM(CASE WHEN t.transaction_type = 'IN' THEN t.qty_in ELSE 0 END), 0) as month_in_qty,
            COALESCE(SUM(CASE WHEN t.transaction_type = 'IN' THEN t.qty_in * t.unit_cost ELSE 0 END), 0) as month_in_value,
            COALESCE(SUM(CASE WHEN t.transaction_type = 'OUT' THEN t.qty_out ELSE 0 END), 0) as month_out_qty,
            COALESCE(SUM(CASE WHEN t.transaction_type = 'OUT' THEN t.qty_out * t.unit_cost ELSE 0 END), 0) as month_out_value
        FROM inv_stock_transactions t
        WHERE t.transaction_date BETWEEN '$firstDayThisMonth' AND '$lastDayThisMonth'
        $whTxWhere
    ";
    $monthData = $pdo->query($sqlMonth)->fetch(PDO::FETCH_ASSOC);

    // 3. Low stock count
    $whOnHandSub = $warehouse_id 
        ? "COALESCE((SELECT SUM(b.balance_qty) FROM inv_stock_balances b WHERE b.item_id = i.id AND b.warehouse_id = $warehouse_id), 0)"
        : "COALESCE((SELECT SUM(b.balance_qty) FROM inv_stock_balances b WHERE b.item_id = i.id), 0)";

    $sqlLow = "
        SELECT COUNT(*) FROM inv_items i 
        WHERE i.active = 1 AND i.minimum_stock > 0 AND $whOnHandSub <= i.minimum_stock
    ";
    $lowCount = (int)$pdo->query($sqlLow)->fetchColumn();

    // 4. Expiring lots in 60 days
    $today = date('Y-m-d');
    $in60Days = date('Y-m-d', strtotime('+60 days'));
    $sqlExp = "
        SELECT COUNT(*) FROM inv_stock_lots l
        WHERE l.balance_qty > 0 
          AND l.expiry_date IS NOT NULL 
          AND l.expiry_date <= '$in60Days'
          $whLotWhere
    ";
    $expCount = (int)$pdo->query($sqlExp)->fetchColumn();

    echo json_encode([
        'status' => 'success',
        'data' => [
            'total_balance_qty' => (float)$bal['total_balance_qty'],
            'total_value' => (float)$bal['total_value'],
            'active_items_count' => (int)$bal['active_items_count'],
            'month_in_qty' => (float)$monthData['month_in_qty'],
            'month_in_value' => (float)$monthData['month_in_value'],
            'month_out_qty' => (float)$monthData['month_out_qty'],
            'month_out_value' => (float)$monthData['month_out_value'],
            'low_stock_count' => $lowCount,
            'expiring_count' => $expCount
        ]
    ]);
}

/**
 * 2. Stock Balance Report
 */
function handleBalance($pdo, $warehouse_id, $category_id, $status, $search) {
    $where = ["i.active = 1"];
    $params = [];

    if ($warehouse_id) {
        $where[] = "b.warehouse_id = ?";
        $params[] = $warehouse_id;
    }

    if ($category_id) {
        $where[] = "i.category_id = ?";
        $params[] = $category_id;
    }

    if ($search !== '') {
        $where[] = "(i.item_code LIKE ? OR i.item_name LIKE ? OR i.barcode LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $whereClause = implode(" AND ", $where);

    $sql = "
        SELECT 
            b.item_id,
            b.warehouse_id,
            w.name as warehouse_name,
            w.code as warehouse_code,
            i.item_code,
            i.item_name,
            c.name as category_name,
            u.name as unit_name,
            COALESCE(i.minimum_stock, 0) as minimum_stock,
            COALESCE(i.maximum_stock, 0) as maximum_stock,
            COALESCE(i.reorder_point, 0) as reorder_point,
            COALESCE(NULLIF(i.standard_price, 0), NULLIF(i.last_purchase_price, 0), 0) as unit_price,
            COALESCE(SUM(b.balance_qty), 0) as balance_qty,
            COALESCE(SUM(b.balance_qty * COALESCE(NULLIF(i.standard_price, 0), NULLIF(i.last_purchase_price, 0), 0)), 0) as total_value
        FROM inv_stock_balances b
        JOIN inv_items i ON b.item_id = i.id
        LEFT JOIN inv_warehouses w ON b.warehouse_id = w.id
        LEFT JOIN inv_categories c ON i.category_id = c.id
        LEFT JOIN inv_units u ON i.unit_id = u.id
        WHERE $whereClause
        GROUP BY b.item_id, b.warehouse_id
        ORDER BY i.item_code ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Filter status if required
    $filtered = [];
    $total_qty = 0;
    $total_value = 0;
    $low_count = 0;

    foreach ($rows as $r) {
        $bal = (float)$r['balance_qty'];
        $min = (float)$r['minimum_stock'];
        $val = (float)$r['total_value'];

        $itemStatus = 'NORMAL';
        if ($bal <= 0) {
            $itemStatus = 'OUT_OF_STOCK';
        } elseif ($min > 0 && $bal <= $min) {
            $itemStatus = 'LOW_STOCK';
            $low_count++;
        }

        if ($status === 'low' && $itemStatus !== 'LOW_STOCK') continue;
        if ($status === 'out_of_stock' && $itemStatus !== 'OUT_OF_STOCK') continue;
        if ($status === 'in_stock' && $bal <= 0) continue;

        $r['balance_qty'] = $bal;
        $r['unit_price'] = (float)$r['unit_price'];
        $r['total_value'] = $val;
        $r['status_label'] = $itemStatus;

        $total_qty += $bal;
        $total_value += $val;
        $filtered[] = $r;
    }

    echo json_encode([
        'status' => 'success',
        'summary' => [
            'total_items' => count($filtered),
            'total_qty' => $total_qty,
            'total_value' => $total_value,
            'low_stock_count' => $low_count
        ],
        'data' => $filtered
    ]);
}

/**
 * 3. Inward / Receiving Report
 */
function handleReceipts($pdo, $warehouse_id, $vendor_id, $start_date, $end_date, $search) {
    $where = ["1=1"];
    $params = [];

    if ($warehouse_id) {
        $where[] = "r.warehouse_id = ?";
        $params[] = $warehouse_id;
    }

    if ($vendor_id) {
        $where[] = "r.vendor_id = ?";
        $params[] = $vendor_id;
    }

    if ($start_date) {
        $where[] = "r.receipt_date >= ?";
        $params[] = $start_date;
    }

    if ($end_date) {
        $where[] = "r.receipt_date <= ?";
        $params[] = $end_date;
    }

    if ($search !== '') {
        $where[] = "(r.receipt_no LIKE ? OR r.invoice_no LIKE ? OR i.item_name LIKE ? OR i.item_code LIKE ? OR v.name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $whereClause = implode(" AND ", $where);

    $sql = "
        SELECT 
            ri.id as receipt_item_id,
            r.id as receipt_id,
            r.receipt_no,
            r.receipt_date,
            w.name as warehouse_name,
            v.name as vendor_name,
            po.po_number,
            r.invoice_no,
            i.item_code,
            i.item_name,
            u.name as unit_name,
            ri.lot_number,
            ri.expiry_date,
            ri.qty,
            ri.unit_cost,
            ri.total_price,
            r.receiver_name,
            r.remark
        FROM inv_receipt_items ri
        JOIN inv_receipts r ON ri.receipt_id = r.id
        JOIN inv_items i ON ri.item_id = i.id
        LEFT JOIN inv_warehouses w ON r.warehouse_id = w.id
        LEFT JOIN inv_vendors v ON r.vendor_id = v.id
        LEFT JOIN inv_purchase_orders po ON r.po_id = po.id
        LEFT JOIN inv_units u ON ri.unit_id = u.id
        WHERE $whereClause
        ORDER BY r.receipt_date DESC, ri.id DESC
        LIMIT 500
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_qty = 0;
    $total_amount = 0;
    foreach ($rows as &$r) {
        $r['qty'] = (float)$r['qty'];
        $r['unit_cost'] = (float)$r['unit_cost'];
        $r['total_price'] = (float)$r['total_price'];
        $total_qty += $r['qty'];
        $total_amount += $r['total_price'];
    }

    echo json_encode([
        'status' => 'success',
        'summary' => [
            'total_count' => count($rows),
            'total_qty' => $total_qty,
            'total_amount' => $total_amount
        ],
        'data' => $rows
    ]);
}

/**
 * 4. Outward / Issue Report
 */
function handleIssues($pdo, $warehouse_id, $start_date, $end_date, $search) {
    $where = ["1=1"];
    $params = [];

    if ($warehouse_id) {
        $where[] = "iss.warehouse_id = ?";
        $params[] = $warehouse_id;
    }

    if ($start_date) {
        $where[] = "iss.issue_date >= ?";
        $params[] = $start_date;
    }

    if ($end_date) {
        $where[] = "iss.issue_date <= ?";
        $params[] = $end_date;
    }

    if ($search !== '') {
        $where[] = "(iss.issue_no LIKE ? OR iss.department_name LIKE ? OR iss.requester_name LIKE ? OR i.item_name LIKE ? OR i.item_code LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $whereClause = implode(" AND ", $where);

    $sql = "
        SELECT 
            ii.id as issue_item_id,
            iss.id as issue_id,
            iss.issue_no,
            iss.issue_date,
            w.name as warehouse_name,
            iss.department_name,
            iss.requester_name,
            i.item_code,
            i.item_name,
            u.name as unit_name,
            l.lot_number,
            ii.qty,
            ii.unit_cost,
            ii.total_price,
            iss.issuer_name,
            iss.remark
        FROM inv_issue_items ii
        JOIN inv_issues iss ON ii.issue_id = iss.id
        JOIN inv_items i ON ii.item_id = i.id
        LEFT JOIN inv_warehouses w ON iss.warehouse_id = w.id
        LEFT JOIN inv_stock_lots l ON ii.lot_id = l.id
        LEFT JOIN inv_units u ON ii.unit_id = u.id
        WHERE $whereClause
        ORDER BY iss.issue_date DESC, ii.id DESC
        LIMIT 500
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_qty = 0;
    $total_amount = 0;
    foreach ($rows as &$r) {
        $r['qty'] = (float)$r['qty'];
        $r['unit_cost'] = (float)$r['unit_cost'];
        $r['total_price'] = (float)$r['total_price'];
        $total_qty += $r['qty'];
        $total_amount += $r['total_price'];
    }

    echo json_encode([
        'status' => 'success',
        'summary' => [
            'total_count' => count($rows),
            'total_qty' => $total_qty,
            'total_amount' => $total_amount
        ],
        'data' => $rows
    ]);
}

/**
 * 5. Movement Summary Report (Beginning -> In -> Out -> Ending)
 */
function handleMovement($pdo, $warehouse_id, $category_id, $start_date, $end_date, $search) {
    if (!$start_date) $start_date = date('Y-m-01 00:00:00');
    if (!$end_date) $end_date = date('Y-m-d 23:59:59');

    $whereItem = ["i.active = 1"];
    $params = [];

    if ($category_id) {
        $whereItem[] = "i.category_id = ?";
        $params[] = $category_id;
    }

    if ($search !== '') {
        $whereItem[] = "(i.item_code LIKE ? OR i.item_name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $whereItemClause = implode(" AND ", $whereItem);

    $whConditionTx = $warehouse_id ? "AND t.warehouse_id = " . (int)$warehouse_id : "";

    $sql = "
        SELECT 
            i.id as item_id,
            i.item_code,
            i.item_name,
            c.name as category_name,
            u.name as unit_name,
            COALESCE(NULLIF(i.standard_price, 0), NULLIF(i.last_purchase_price, 0), 0) as unit_price,
            
            -- Beginning balance before start_date
            COALESCE((
                SELECT SUM(CASE WHEN t.transaction_type = 'IN' THEN t.qty_in ELSE -t.qty_out END)
                FROM inv_stock_transactions t
                WHERE t.item_id = i.id AND t.transaction_date < ? $whConditionTx
            ), 0) as beginning_qty,
            
            -- Period IN
            COALESCE((
                SELECT SUM(t.qty_in)
                FROM inv_stock_transactions t
                WHERE t.item_id = i.id AND t.transaction_type = 'IN' 
                  AND t.transaction_date BETWEEN ? AND ? $whConditionTx
            ), 0) as in_qty,
            
            -- Period OUT
            COALESCE((
                SELECT SUM(t.qty_out)
                FROM inv_stock_transactions t
                WHERE t.item_id = i.id AND t.transaction_type = 'OUT'
                  AND t.transaction_date BETWEEN ? AND ? $whConditionTx
            ), 0) as out_qty
            
        FROM inv_items i
        LEFT JOIN inv_categories c ON i.category_id = c.id
        LEFT JOIN inv_units u ON i.unit_id = u.id
        WHERE $whereItemClause
        ORDER BY i.item_code ASC
        LIMIT 500
    ";

    $execParams = array_merge([$start_date, $start_date, $end_date, $start_date, $end_date], $params);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($execParams);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filtered = [];
    $total_begin = 0;
    $total_in = 0;
    $total_out = 0;
    $total_end = 0;
    $total_val = 0;

    foreach ($rows as $r) {
        $begin = (float)$r['beginning_qty'];
        $in = (float)$r['in_qty'];
        $out = (float)$r['out_qty'];
        $end = $begin + $in - $out;
        $price = (float)$r['unit_price'];
        $ending_value = $end * $price;

        // Skip items with zero activity and zero balances to keep report clean
        if ($begin == 0 && $in == 0 && $out == 0 && $end == 0) {
            continue;
        }

        $r['beginning_qty'] = $begin;
        $r['in_qty'] = $in;
        $r['out_qty'] = $out;
        $r['ending_qty'] = $end;
        $r['unit_price'] = $price;
        $r['ending_value'] = $ending_value;

        $total_begin += $begin;
        $total_in += $in;
        $total_out += $out;
        $total_end += $end;
        $total_val += $ending_value;

        $filtered[] = $r;
    }

    echo json_encode([
        'status' => 'success',
        'summary' => [
            'total_items' => count($filtered),
            'total_beginning' => $total_begin,
            'total_in' => $total_in,
            'total_out' => $total_out,
            'total_ending' => $total_end,
            'total_ending_value' => $total_val,
            'start_date' => $start_date,
            'end_date' => $end_date
        ],
        'data' => $filtered
    ]);
}

/**
 * 6. Expiring & Aging Report
 */
function handleExpiring($pdo, $warehouse_id, $days, $search) {
    $today = date('Y-m-d');
    $targetDate = date('Y-m-d', strtotime("+$days days"));

    $where = ["l.balance_qty > 0", "l.expiry_date IS NOT NULL"];
    $params = [];

    if ($warehouse_id) {
        $where[] = "l.warehouse_id = ?";
        $params[] = $warehouse_id;
    }

    if ($days === -1) {
        // Already expired
        $where[] = "l.expiry_date < ?";
        $params[] = $today;
    } else {
        $where[] = "l.expiry_date <= ?";
        $params[] = $targetDate;
    }

    if ($search !== '') {
        $where[] = "(i.item_code LIKE ? OR i.item_name LIKE ? OR l.lot_number LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $whereClause = implode(" AND ", $where);

    $sql = "
        SELECT 
            l.id as lot_id,
            l.item_id,
            l.warehouse_id,
            w.name as warehouse_name,
            i.item_code,
            i.item_name,
            c.name as category_name,
            u.name as unit_name,
            l.lot_number,
            l.expiry_date,
            DATEDIFF(l.expiry_date, '$today') as days_remaining,
            l.balance_qty,
            l.unit_cost,
            (l.balance_qty * l.unit_cost) as total_value
        FROM inv_stock_lots l
        JOIN inv_items i ON l.item_id = i.id
        LEFT JOIN inv_warehouses w ON l.warehouse_id = w.id
        LEFT JOIN inv_categories c ON i.category_id = c.id
        LEFT JOIN inv_units u ON i.unit_id = u.id
        WHERE $whereClause
        ORDER BY l.expiry_date ASC
        LIMIT 300
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_qty = 0;
    $total_value = 0;
    $expired_count = 0;
    $urgent_count = 0;

    foreach ($rows as &$r) {
        $r['days_remaining'] = (int)$r['days_remaining'];
        $r['balance_qty'] = (float)$r['balance_qty'];
        $r['unit_cost'] = (float)$r['unit_cost'];
        $r['total_value'] = (float)$r['total_value'];

        if ($r['days_remaining'] < 0) {
            $r['status_badge'] = 'EXPIRED';
            $r['status_text'] = 'หมดอายุแล้ว (' . abs($r['days_remaining']) . ' วัน)';
            $expired_count++;
        } elseif ($r['days_remaining'] <= 30) {
            $r['status_badge'] = 'URGENT';
            $r['status_text'] = 'เร่งด่วน (' . $r['days_remaining'] . ' วัน)';
            $urgent_count++;
        } else {
            $r['status_badge'] = 'WARNING';
            $r['status_text'] = 'เตือน (' . $r['days_remaining'] . ' วัน)';
        }

        $total_qty += $r['balance_qty'];
        $total_value += $r['total_value'];
    }

    echo json_encode([
        'status' => 'success',
        'summary' => [
            'total_lots' => count($rows),
            'total_qty' => $total_qty,
            'total_value' => $total_value,
            'expired_count' => $expired_count,
            'urgent_count' => $urgent_count
        ],
        'data' => $rows
    ]);
}
