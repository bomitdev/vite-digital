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

$from_warehouse_id = (int)($data['from_warehouse_id'] ?? 0);
$to_warehouse_id = (int)($data['to_warehouse_id'] ?? 0);
$transfer_date = $data['transfer_date'] ?? date('Y-m-d H:i:s');
$transfer_no = trim($data['transfer_no'] ?? '');
$remark = trim($data['remark'] ?? '');
$user_name = trim($data['user_name'] ?? 'System');
$items = $data['items'] ?? [];

if (!$from_warehouse_id || !$to_warehouse_id || $from_warehouse_id === $to_warehouse_id || !$transfer_no || empty($items)) {
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน หรือ คลังต้นทาง/ปลายทางซ้ำกัน']);
    exit;
}

try {
    $pdo2->beginTransaction();

    // 1. Insert Transfer Record
    $stmt = $pdo2->prepare("
        INSERT INTO inv_transfers (transfer_no, transfer_date, from_warehouse_id, to_warehouse_id, user_name, remark, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'COMPLETED')
    ");
    $stmt->execute([$transfer_no, $transfer_date, $from_warehouse_id, $to_warehouse_id, $user_name, $remark]);
    $transfer_id = $pdo2->lastInsertId();

    $stmtTransferItem = $pdo2->prepare("INSERT INTO inv_transfer_items (transfer_id, item_id, lot_id, qty) VALUES (?, ?, ?, ?)");

    $stmtGetSourceLot = $pdo2->prepare("SELECT * FROM inv_stock_lots WHERE id = ? AND warehouse_id = ? FOR UPDATE");
    $stmtGetDestLot = $pdo2->prepare("SELECT * FROM inv_stock_lots WHERE item_id = ? AND warehouse_id = ? AND lot_number = ? LIMIT 1 FOR UPDATE");
    
    $stmtUpdateSourceLot = $pdo2->prepare("UPDATE inv_stock_lots SET balance_qty = balance_qty - ? WHERE id = ?");
    $stmtUpdateDestLot = $pdo2->prepare("UPDATE inv_stock_lots SET balance_qty = balance_qty + ?, receive_qty = receive_qty + ? WHERE id = ?");
    $stmtInsertDestLot = $pdo2->prepare("INSERT INTO inv_stock_lots (item_id, warehouse_id, lot_number, expiry_date, unit_cost, receive_qty, balance_qty) VALUES (?, ?, ?, ?, ?, ?, ?)");

    $stmtGetBalance = $pdo2->prepare("SELECT balance_qty FROM inv_stock_balances WHERE warehouse_id = ? AND item_id = ? FOR UPDATE");
    $stmtUpdateBalance = $pdo2->prepare("UPDATE inv_stock_balances SET balance_qty = balance_qty + ? WHERE warehouse_id = ? AND item_id = ?");
    $stmtInsertBalance = $pdo2->prepare("INSERT INTO inv_stock_balances (warehouse_id, item_id, balance_qty) VALUES (?, ?, ?)");

    $stmtTx = $pdo2->prepare("
        INSERT INTO inv_stock_transactions 
        (warehouse_id, item_id, lot_id, document_type, document_id, transaction_type, qty_out, qty_in, unit_cost, balance_qty, transaction_date, user_name, remark)
        VALUES (?, ?, ?, 'TRANSFER', ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($items as $item) {
        $item_id = (int)$item['item_id'];
        $lot_id = (int)($item['lot_id'] ?? 0);
        $qty = (float)$item['qty'];
        
        if ($qty <= 0) continue;

        // Verify Source Lot
        $stmtGetSourceLot->execute([$lot_id, $from_warehouse_id]);
        $sourceLot = $stmtGetSourceLot->fetch(PDO::FETCH_ASSOC);
        
        if (!$sourceLot || $sourceLot['balance_qty'] < $qty) {
            throw new Exception("ยอดคงเหลือใน Lot ต้นทางไม่เพียงพอ (Item ID: {$item_id})");
        }
        
        $unit_cost = (float)$sourceLot['unit_cost'];
        $lot_number = $sourceLot['lot_number'];
        $expiry_date = $sourceLot['expiry_date'];

        // --- SOURCE WAREHOUSE ---
        // Decrease Source Lot
        $stmtUpdateSourceLot->execute([$qty, $lot_id]);
        
        // Decrease Source Balance
        $stmtGetBalance->execute([$from_warehouse_id, $item_id]);
        $srcBal = $stmtGetBalance->fetch(PDO::FETCH_ASSOC);
        $newSrcBal = (float)$srcBal['balance_qty'] - $qty;
        $stmtUpdateBalance->execute([-$qty, $from_warehouse_id, $item_id]);
        
        // Transaction OUT
        $stmtTx->execute([
            $from_warehouse_id, $item_id, $lot_id, $transfer_id, 'OUT', $qty, 0, $unit_cost, $newSrcBal, $transfer_date, $user_name, "Transfer to WH {$to_warehouse_id}"
        ]);

        // --- DESTINATION WAREHOUSE ---
        // Check Dest Lot
        $dest_lot_id = null;
        $stmtGetDestLot->execute([$item_id, $to_warehouse_id, $lot_number]);
        $destLot = $stmtGetDestLot->fetch(PDO::FETCH_ASSOC);
        
        if ($destLot) {
            $dest_lot_id = $destLot['id'];
            $stmtUpdateDestLot->execute([$qty, $qty, $dest_lot_id]);
        } else {
            $stmtInsertDestLot->execute([$item_id, $to_warehouse_id, $lot_number, $expiry_date, $unit_cost, $qty, $qty]);
            $dest_lot_id = $pdo2->lastInsertId();
        }

        // Increase Dest Balance
        $stmtGetBalance->execute([$to_warehouse_id, $item_id]);
        $destBal = $stmtGetBalance->fetch(PDO::FETCH_ASSOC);
        if ($destBal) {
            $newDestBal = (float)$destBal['balance_qty'] + $qty;
            $stmtUpdateBalance->execute([$qty, $to_warehouse_id, $item_id]);
        } else {
            $newDestBal = $qty;
            $stmtInsertBalance->execute([$to_warehouse_id, $item_id, $qty]);
        }
        
        // Transaction IN
        $stmtTx->execute([
            $to_warehouse_id, $item_id, $dest_lot_id, $transfer_id, 'IN', 0, $qty, $unit_cost, $newDestBal, $transfer_date, $user_name, "Transfer from WH {$from_warehouse_id}"
        ]);

        // Insert Transfer Item
        $stmtTransferItem->execute([$transfer_id, $item_id, $sourceLot['id'], $qty]);
    }

    $pdo2->commit();
    echo json_encode(['status' => 'success', 'message' => 'โอนย้ายวัสดุสำเร็จ']);

} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
