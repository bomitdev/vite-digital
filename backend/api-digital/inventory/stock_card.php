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
    // Fetch Stock Balance
    $stmtBal = $pdo2->prepare("SELECT balance_qty FROM inv_stock_balances WHERE item_id = ? AND warehouse_id = ?");
    $stmtBal->execute([$item_id, $warehouse_id]);
    $balance_row = $stmtBal->fetch();
    $current_balance = $balance_row ? (float)$balance_row['balance_qty'] : 0;

    // Fetch Item default pricing fallback
    $stmtItem = $pdo2->prepare("
        SELECT COALESCE(NULLIF(ave_rate, 0), NULLIF(standard_price, 0), NULLIF(last_purchase_price, 0), 0) AS default_price
        FROM inv_items
        WHERE id = ?
        LIMIT 1
    ");
    $stmtItem->execute([$item_id]);
    $itemDefaultPrice = (float)($stmtItem->fetchColumn() ?: 0);

    // Fetch Current Lots Value
    $stmtLotVal = $pdo2->prepare("
        SELECT
            COALESCE(SUM(balance_qty), 0) AS lot_qty,
            COALESCE(SUM(balance_qty * COALESCE(NULLIF(unit_cost, 0), ?)), 0) AS lot_value
        FROM inv_stock_lots
        WHERE item_id = ? AND warehouse_id = ? AND balance_qty > 0
    ");
    $stmtLotVal->execute([$itemDefaultPrice, $item_id, $warehouse_id]);
    $lotSummary = $stmtLotVal->fetch(PDO::FETCH_ASSOC);
    $lotQty = (float)($lotSummary['lot_qty'] ?? 0);
    $current_total_value = $lotQty > 0 ? (float)$lotSummary['lot_value'] : ($current_balance * $itemDefaultPrice);
    $current_unit_cost = $current_balance > 0 ? ($current_total_value / $current_balance) : $itemDefaultPrice;

    // Fetch Transactions
    $stmtTx = $pdo2->prepare("
        SELECT 
            t.*,
            l.lot_number,
            l.expiry_date,
            COALESCE(NULLIF(t.unit_cost, 0), NULLIF(l.unit_cost, 0), NULLIF(i.ave_rate, 0), NULLIF(i.standard_price, 0), NULLIF(i.last_purchase_price, 0), 0) AS effective_unit_cost,
            (CASE WHEN t.qty_in > 0 THEN t.qty_in ELSE t.qty_out END) *
                COALESCE(NULLIF(t.unit_cost, 0), NULLIF(l.unit_cost, 0), NULLIF(i.ave_rate, 0), NULLIF(i.standard_price, 0), NULLIF(i.last_purchase_price, 0), 0) AS total_value
        FROM inv_stock_transactions t
        LEFT JOIN inv_stock_lots l ON t.lot_id = l.id
        LEFT JOIN inv_items i ON t.item_id = i.id
        WHERE t.item_id = ? AND t.warehouse_id = ?
        ORDER BY t.transaction_date DESC, t.id DESC
        LIMIT 100
    ");
    $stmtTx->execute([$item_id, $warehouse_id]);
    $transactions = $stmtTx->fetchAll();

    echo json_encode([
        'status' => 'success', 
        'current_balance' => $current_balance,
        'current_unit_cost' => round($current_unit_cost, 2),
        'current_total_value' => round($current_total_value, 2),
        'data' => $transactions
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
