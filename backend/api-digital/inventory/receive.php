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
$receipt_date = $data['receipt_date'] ?? date('Y-m-d H:i:s');
$receipt_no = trim($data['receipt_no'] ?? '');
$vendor_id = isset($data['vendor_id']) && $data['vendor_id'] !== '' ? (int)$data['vendor_id'] : null;
$po_id = isset($data['po_id']) && $data['po_id'] !== '' ? (int)$data['po_id'] : null;
$invoice_no = trim($data['invoice_no'] ?? '');
$remark = trim($data['remark'] ?? '');
$user_name = trim($data['user_name'] ?? 'System');
$items = $data['items'] ?? [];

if (!$warehouse_id || !$receipt_no || empty($items)) {
    echo json_encode(['status' => 'error', 'message' => 'Warehouse, Receipt No, and Items are required.']);
    exit;
}

try {
    $pdo2->beginTransaction();

    // Calculate total amount
    $total_amount = 0;
    foreach ($items as $item) {
        $qty = (float)($item['qty'] ?? 0);
        $cost = (float)($item['unit_cost'] ?? 0);
        $total_amount += ($qty * $cost);
    }

    // 1. Insert Receipt
    $stmt = $pdo2->prepare("
        INSERT INTO inv_receipts 
        (receipt_no, receipt_date, warehouse_id, vendor_id, po_id, invoice_no, total_amount, receiver_name, remark, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'COMPLETED')
    ");
    $stmt->execute([
        $receipt_no, $receipt_date, $warehouse_id, $vendor_id, $po_id, $invoice_no, $total_amount, $user_name, $remark
    ]);
    $receipt_id = $pdo2->lastInsertId();

    // Statements for inside the loop
    $stmtReceiptItem = $pdo2->prepare("
        INSERT INTO inv_receipt_items (receipt_id, item_id, lot_number, expiry_date, qty, unit_id, unit_cost, total_price)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmtGetLot = $pdo2->prepare("SELECT id, balance_qty FROM inv_stock_lots WHERE item_id = ? AND warehouse_id = ? AND lot_number = ? LIMIT 1");
    $stmtInsertLot = $pdo2->prepare("INSERT INTO inv_stock_lots (item_id, warehouse_id, lot_number, expiry_date, unit_cost, receive_qty, balance_qty) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmtUpdateLot = $pdo2->prepare("UPDATE inv_stock_lots SET balance_qty = balance_qty + ?, receive_qty = receive_qty + ? WHERE id = ?");
    
    $stmtGetBalance = $pdo2->prepare("SELECT balance_qty FROM inv_stock_balances WHERE warehouse_id = ? AND item_id = ? LIMIT 1");
    $stmtInsertBalance = $pdo2->prepare("INSERT INTO inv_stock_balances (warehouse_id, item_id, balance_qty) VALUES (?, ?, ?)");
    $stmtUpdateBalance = $pdo2->prepare("UPDATE inv_stock_balances SET balance_qty = balance_qty + ? WHERE warehouse_id = ? AND item_id = ?");
    
    $stmtTx = $pdo2->prepare("
        INSERT INTO inv_stock_transactions 
        (warehouse_id, item_id, lot_id, document_type, document_id, transaction_type, qty_in, unit_cost, balance_qty, transaction_date, user_name, remark)
        VALUES (?, ?, ?, 'RECEIVE', ?, 'IN', ?, ?, ?, ?, ?, ?)
    ");
    
    $stmtUpdatePOItem = null;
    if ($po_id) {
        $stmtUpdatePOItem = $pdo2->prepare("UPDATE inv_purchase_order_items SET qty_received = COALESCE(qty_received, 0) + ? WHERE po_id = ? AND item_id = ?");
    }

    // 2. Process Items
    foreach ($items as $item) {
        $item_id = (int)$item['item_id'];
        $qty = (float)$item['qty'];
        $unit_id = (int)$item['unit_id'];
        $unit_cost = (float)($item['unit_cost'] ?? 0);
        $total_price = $qty * $unit_cost;
        $lot_number = trim($item['lot_number'] ?? '');
        $expiry_date = !empty($item['expiry_date']) ? $item['expiry_date'] : null;
        
        // Insert Receipt Item
        $stmtReceiptItem->execute([$receipt_id, $item_id, $lot_number, $expiry_date, $qty, $unit_id, $unit_cost, $total_price]);
        
        // Manage Lot
        $lot_id = null;
        if ($lot_number !== '') {
            $stmtGetLot->execute([$item_id, $warehouse_id, $lot_number]);
            $existing_lot = $stmtGetLot->fetch();
            
            if ($existing_lot) {
                $lot_id = $existing_lot['id'];
                $stmtUpdateLot->execute([$qty, $qty, $lot_id]);
            } else {
                $stmtInsertLot->execute([$item_id, $warehouse_id, $lot_number, $expiry_date, $unit_cost, $qty, $qty]);
                $lot_id = $pdo2->lastInsertId();
            }
        }
        
        // Manage Stock Balance
        $stmtGetBalance->execute([$warehouse_id, $item_id]);
        $existing_balance = $stmtGetBalance->fetch();
        
        $new_balance = $qty;
        if ($existing_balance) {
            $new_balance += (float)$existing_balance['balance_qty'];
            $stmtUpdateBalance->execute([$qty, $warehouse_id, $item_id]);
        } else {
            $stmtInsertBalance->execute([$warehouse_id, $item_id, $qty]);
        }
        
        // Insert Stock Transaction
        $stmtTx->execute([
            $warehouse_id, $item_id, $lot_id, $receipt_id, $qty, $unit_cost, $new_balance, $receipt_date, $user_name, 'RECEIVE from ' . $receipt_no
        ]);
        
        // Update PO Item
        if ($stmtUpdatePOItem) {
            $stmtUpdatePOItem->execute([$qty, $po_id, $item_id]);
        }
    }
    
    // 3. Update PO Status
    if ($po_id) {
        $stmtCheckPO = $pdo2->prepare("SELECT COUNT(*) FROM inv_purchase_order_items WHERE po_id = ? AND qty_ordered > COALESCE(qty_received, 0)");
        $stmtCheckPO->execute([$po_id]);
        $unreceived = (int)$stmtCheckPO->fetchColumn();
        
        if ($unreceived > 0) {
            $stmtUpdatePOStatus = $pdo2->prepare("UPDATE inv_purchase_orders SET status = 'PARTIAL_RECEIVED' WHERE id = ?");
        } else {
            $stmtUpdatePOStatus = $pdo2->prepare("UPDATE inv_purchase_orders SET status = 'COMPLETED' WHERE id = ?");
        }
        $stmtUpdatePOStatus->execute([$po_id]);
    }

    $pdo2->commit();
    echo json_encode(['status' => 'success', 'message' => 'Stock Received successfully', 'receipt_id' => $receipt_id]);

} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
