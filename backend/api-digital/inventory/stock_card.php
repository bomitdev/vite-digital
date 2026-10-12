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

    // Fetch Transactions
    $stmtTx = $pdo2->prepare("
        SELECT t.*, l.lot_number, l.expiry_date 
        FROM inv_stock_transactions t
        LEFT JOIN inv_stock_lots l ON t.lot_id = l.id
        WHERE t.item_id = ? AND t.warehouse_id = ?
        ORDER BY t.transaction_date DESC, t.id DESC
        LIMIT 100
    ");
    $stmtTx->execute([$item_id, $warehouse_id]);
    $transactions = $stmtTx->fetchAll();

    echo json_encode([
        'status' => 'success', 
        'current_balance' => $current_balance,
        'data' => $transactions
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
