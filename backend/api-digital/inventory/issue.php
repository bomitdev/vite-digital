<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST is allowed.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

// Validate main inputs
$warehouse_id = (int)($data['warehouse_id'] ?? 0);
$issue_date = $data['issue_date'] ?? date('Y-m-d H:i:s');
$issue_no = trim($data['issue_no'] ?? '');
$department_id = isset($data['department_id']) && $data['department_id'] !== '' ? (int)$data['department_id'] : null;
$department_name = trim($data['department_name'] ?? '');
$requester_name = trim($data['requester_name'] ?? '');
$remark = trim($data['remark'] ?? '');
$user_name = trim($data['user_name'] ?? 'System');
$items = $data['items'] ?? [];

if (!$warehouse_id || !$issue_no || empty($items)) {
    echo json_encode(['status' => 'error', 'message' => 'Warehouse, Issue No, and Items are required.']);
    exit;
}

try {
    $pdo2->beginTransaction();

    // 1. Calculate total amount and check stock balance BEFORE insert
    $total_amount = 0;
    $stmtCheckLot = $pdo2->prepare("SELECT balance_qty, unit_cost FROM inv_stock_lots WHERE id = ? FOR UPDATE");
    $stmtCheckBal = $pdo2->prepare("SELECT balance_qty FROM inv_stock_balances WHERE warehouse_id = ? AND item_id = ? FOR UPDATE");

    foreach ($items as $item) {
        $lot_id = (int)$item['lot_id'];
        $qty_requested = (float)$item['qty'];
        
        $stmtCheckLot->execute([$lot_id]);
        $lot = $stmtCheckLot->fetch();
        if (!$lot || (float)$lot['balance_qty'] < $qty_requested) {
            throw new Exception("Stock in Lot ID $lot_id is insufficient.");
        }
        $total_amount += ($qty_requested * (float)$lot['unit_cost']);
    }

    // 2. Insert Issue Document
    $stmt = $pdo2->prepare("
        INSERT INTO inv_issues 
        (issue_no, issue_date, warehouse_id, department_id, department_name, total_amount, requester_name, issuer_name, remark, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'COMPLETED')
    ");
    $stmt->execute([
        $issue_no, $issue_date, $warehouse_id, $department_id, $department_name, $total_amount, $requester_name, $user_name, $remark
    ]);
    $issue_id = $pdo2->lastInsertId();

    // 3. Process Items and deduct stock
    $stmtIssueItem = $pdo2->prepare("
        INSERT INTO inv_issue_items (issue_id, item_id, lot_id, qty, unit_id, unit_cost, total_price)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtDeductLot = $pdo2->prepare("UPDATE inv_stock_lots SET balance_qty = balance_qty - ? WHERE id = ?");
    $stmtDeductBal = $pdo2->prepare("UPDATE inv_stock_balances SET balance_qty = balance_qty - ? WHERE warehouse_id = ? AND item_id = ?");
    $stmtTx = $pdo2->prepare("
        INSERT INTO inv_stock_transactions 
        (warehouse_id, item_id, lot_id, document_type, document_id, transaction_type, qty_out, unit_cost, balance_qty, transaction_date, user_name, remark)
        VALUES (?, ?, ?, 'ISSUE', ?, 'OUT', ?, ?, ?, ?, ?, ?)
    ");

    foreach ($items as $item) {
        $item_id = (int)$item['item_id'];
        $lot_id = (int)$item['lot_id'];
        $qty = (float)$item['qty'];
        $unit_id = (int)$item['unit_id'];
        
        // Re-fetch unit cost from lot
        $stmtCheckLot->execute([$lot_id]);
        $lot = $stmtCheckLot->fetch();
        $unit_cost = (float)$lot['unit_cost'];
        $total_price = $qty * $unit_cost;

        // Insert Issue Item
        $stmtIssueItem->execute([$issue_id, $item_id, $lot_id, $qty, $unit_id, $unit_cost, $total_price]);
        
        // Deduct from Lot
        $stmtDeductLot->execute([$qty, $lot_id]);

        // Deduct from Stock Balance
        $stmtDeductBal->execute([$qty, $warehouse_id, $item_id]);
        
        // Fetch new Stock Balance for log
        $stmtCheckBal->execute([$warehouse_id, $item_id]);
        $new_balance = (float)$stmtCheckBal->fetch()['balance_qty'];

        // Insert Transaction Log
        $stmtTx->execute([
            $warehouse_id, $item_id, $lot_id, $issue_id, $qty, $unit_cost, $new_balance, $issue_date, $user_name, 'ISSUE to ' . $department_name
        ]);
    }

    $pdo2->commit();
    echo json_encode(['status' => 'success', 'message' => 'Stock Issued successfully', 'issue_id' => $issue_id]);

} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
