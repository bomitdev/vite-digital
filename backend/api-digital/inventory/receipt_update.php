<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';
require_once __DIR__ . '/inv_auth_helper.php';
require_once __DIR__ . '/inv_log_helper.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Only POST method is allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$receipt_id = (int)($input['id'] ?? 0);
$receipt_date_raw = trim((string)($input['receipt_date'] ?? ''));
$invoice_no = trim((string)($input['invoice_no'] ?? ''));
$vendor_id = !empty($input['vendor_id']) ? (int)$input['vendor_id'] : null;
$remark = trim((string)($input['remark'] ?? ''));
$items = $input['items'] ?? [];

if ($receipt_id <= 0 || !is_array($items) || count($items) === 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน หรือไม่มีรายการวัสดุในใบรับเข้า']);
    exit;
}

$ts = $receipt_date_raw !== '' ? strtotime($receipt_date_raw) : false;
if ($ts === false) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'รูปแบบวันที่รับเข้าไม่ถูกต้อง']);
    exit;
}
$receipt_date = date('Y-m-d H:i:s', $ts);

try {
    $pdo2->beginTransaction();

    // 1. Lock receipt
    $stRc = $pdo2->prepare("SELECT * FROM inv_receipts WHERE id = ? FOR UPDATE");
    $stRc->execute([$receipt_id]);
    $rc = $stRc->fetch(PDO::FETCH_ASSOC);
    if (!$rc) {
        throw new Exception('ไม่พบข้อมูลใบรับเข้า');
    }

    $warehouse_id = (int)$rc['warehouse_id'];
    $currentUser = getInvCurrentUser($pdo3 ?? null);
    if (!isInvWarehouseManager($pdo2, $pdo3 ?? null, $warehouse_id, $currentUser)) {
        $pdo2->rollBack();
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'เฉพาะผู้ดูแลระบบหรือผู้จัดการคลังเท่านั้นที่แก้ไขใบรับเข้าได้']);
        exit;
    }

    // 2. Reverse old RECEIVE transactions on inv_stock_lots
    $stOldTx = $pdo2->prepare("
        SELECT id, item_id, lot_id, qty_in, user_name
        FROM inv_stock_transactions
        WHERE document_type IN ('RECEIVE', 'RECEIPT') AND document_id = ?
        FOR UPDATE
    ");
    $stOldTx->execute([$receipt_id]);
    $oldTxs = $stOldTx->fetchAll(PDO::FETCH_ASSOC);

    $affectedItemIds = [];
    $touchedLotIds = [];
    $actorName = trim((string)($currentUser['fullname'] ?? '')) ?: ($rc['receiver_name'] ?: 'ผู้ดูแลระบบ');

    $stLotLock = $pdo2->prepare("SELECT id, receive_qty, balance_qty FROM inv_stock_lots WHERE id = ? FOR UPDATE");
    $stLotRev = $pdo2->prepare("UPDATE inv_stock_lots SET receive_qty = GREATEST(0, receive_qty - ?), balance_qty = balance_qty - ? WHERE id = ?");

    foreach ($oldTxs as $otx) {
        $affectedItemIds[(int)$otx['item_id']] = true;
        $lid = (int)($otx['lot_id'] ?? 0);
        if ($lid > 0) {
            $stLotLock->execute([$lid]);
            $lotRow = $stLotLock->fetch(PDO::FETCH_ASSOC);
            if ($lotRow) {
                $stLotRev->execute([(float)$otx['qty_in'], (float)$otx['qty_in'], $lid]);
                $touchedLotIds[$lid] = (int)$otx['item_id'];
            }
        }
    }

    // Delete old receipt items & old RECEIVE transactions
    $pdo2->prepare("DELETE FROM inv_receipt_items WHERE receipt_id = ?")->execute([$receipt_id]);
    $pdo2->prepare("DELETE FROM inv_stock_transactions WHERE document_type IN ('RECEIVE', 'RECEIPT') AND document_id = ?")->execute([$receipt_id]);

    // 3. Insert new receipt items & apply to lots
    $stInsRcItem = $pdo2->prepare("
        INSERT INTO inv_receipt_items (receipt_id, item_id, lot_number, expiry_date, qty, unit_id, unit_cost, total_price)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stFindLot = $pdo2->prepare("SELECT id FROM inv_stock_lots WHERE item_id = ? AND warehouse_id = ? AND lot_number = ? LIMIT 1 FOR UPDATE");
    $stUpdLot = $pdo2->prepare("UPDATE inv_stock_lots SET receive_qty = receive_qty + ?, balance_qty = balance_qty + ?, unit_cost = ?, expiry_date = ? WHERE id = ?");
    $stInsLot = $pdo2->prepare("INSERT INTO inv_stock_lots (item_id, warehouse_id, lot_number, expiry_date, unit_cost, receive_qty, balance_qty) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stInsTx = $pdo2->prepare("
        INSERT INTO inv_stock_transactions
        (warehouse_id, item_id, lot_id, document_type, document_id, transaction_type, qty_in, unit_cost, balance_qty, transaction_date, user_name, remark)
        VALUES (?, ?, ?, 'RECEIVE', ?, 'IN', ?, ?, 0, ?, ?, ?)
    ");
    $stItemUnit = $pdo2->prepare("SELECT unit_id, item_name FROM inv_items WHERE id = ? LIMIT 1");

    $total_amount = 0.0;
    foreach ($items as $it) {
        $item_id = (int)($it['item_id'] ?? 0);
        $qty = (float)($it['qty'] ?? 0);
        $unit_cost = (float)($it['unit_cost'] ?? 0);
        $lot_number = trim((string)($it['lot_number'] ?? ''));
        $expiry_date = !empty($it['expiry_date']) ? trim((string)$it['expiry_date']) : null;

        if ($item_id <= 0 || $qty <= 0) {
            throw new Exception('กรุณาระบุรายการวัสดุและจำนวนรับเข้าที่มากกว่า 0');
        }

        $affectedItemIds[$item_id] = true;
        $stItemUnit->execute([$item_id]);
        $itemMeta = $stItemUnit->fetch(PDO::FETCH_ASSOC);
        if (!$itemMeta) {
            throw new Exception("ไม่พบข้อมูลวัสดุ ID {$item_id}");
        }
        $unit_id = !empty($it['unit_id']) ? (int)$it['unit_id'] : (int)($itemMeta['unit_id'] ?? 1);
        $total_price = round($qty * $unit_cost, 2);
        $total_amount += $total_price;

        $stInsRcItem->execute([$receipt_id, $item_id, $lot_number, $expiry_date, $qty, $unit_id, $unit_cost, $total_price]);

        $effectiveLotNo = $lot_number !== '' ? $lot_number : '-';
        $lot_id = null;
        if ($lot_number !== '') {
            $stFindLot->execute([$item_id, $warehouse_id, $effectiveLotNo]);
            $existingLotId = $stFindLot->fetchColumn();
            if ($existingLotId) {
                $lot_id = (int)$existingLotId;
                $stUpdLot->execute([$qty, $qty, $unit_cost, $expiry_date, $lot_id]);
            }
        }
        if (!$lot_id) {
            $stInsLot->execute([$item_id, $warehouse_id, $effectiveLotNo, $expiry_date, $unit_cost, $qty, $qty]);
            $lot_id = (int)$pdo2->lastInsertId();
        }
        $touchedLotIds[$lot_id] = $item_id;

        $stInsTx->execute([
            $warehouse_id,
            $item_id,
            $lot_id,
            $receipt_id,
            $qty,
            $unit_cost,
            $receipt_date,
            $actorName,
            'RECEIVE from ' . $rc['receipt_no']
        ]);
    }

    // 4. Verify no touched lot went negative (due to prior issues)
    if (!empty($touchedLotIds)) {
        $stCheckNeg = $pdo2->prepare("SELECT l.id, l.lot_number, l.balance_qty, i.item_name FROM inv_stock_lots l LEFT JOIN inv_items i ON l.item_id = i.id WHERE l.id = ?");
        foreach (array_keys($touchedLotIds) as $lid) {
            $stCheckNeg->execute([$lid]);
            $lRow = $stCheckNeg->fetch(PDO::FETCH_ASSOC);
            if ($lRow && (float)$lRow['balance_qty'] < -0.0001) {
                throw new Exception("ไม่สามารถลดจำนวนหรือลบรายการ \"{$lRow['item_name']}\" (Lot: {$lRow['lot_number']}) ได้ เนื่องจากมีการเบิกจ่ายจาก Lot นี้ไปแล้ว");
            }
        }
    }

    // 5. Update receipt header
    $stUpdRc = $pdo2->prepare("
        UPDATE inv_receipts
        SET receipt_date = ?, invoice_no = ?, vendor_id = ?, remark = ?, total_amount = ?
        WHERE id = ?
    ");
    $stUpdRc->execute([$receipt_date, $invoice_no, $vendor_id, $remark, $total_amount, $receipt_id]);

    // 6. Recalculate inv_stock_balances and running balance_qty in inv_stock_transactions for all affected items
    $stLotSum = $pdo2->prepare("SELECT COALESCE(SUM(balance_qty), 0) FROM inv_stock_lots WHERE warehouse_id = ? AND item_id = ?");
    $stCheckBal = $pdo2->prepare("SELECT id FROM inv_stock_balances WHERE warehouse_id = ? AND item_id = ? LIMIT 1");
    $stUpdBal = $pdo2->prepare("UPDATE inv_stock_balances SET balance_qty = ? WHERE id = ?");
    $stInsBal = $pdo2->prepare("INSERT INTO inv_stock_balances (warehouse_id, item_id, balance_qty) VALUES (?, ?, ?)");
    $stTxList = $pdo2->prepare("SELECT id, qty_in, qty_out FROM inv_stock_transactions WHERE warehouse_id = ? AND item_id = ? ORDER BY transaction_date ASC, id ASC");
    $stUpdTxBal = $pdo2->prepare("UPDATE inv_stock_transactions SET balance_qty = ? WHERE id = ?");

    foreach (array_keys($affectedItemIds) as $iid) {
        $stLotSum->execute([$warehouse_id, $iid]);
        $newBal = (float)$stLotSum->fetchColumn();
        $stCheckBal->execute([$warehouse_id, $iid]);
        $balId = $stCheckBal->fetchColumn();
        if ($balId) {
            $stUpdBal->execute([$newBal, $balId]);
        } else {
            $stInsBal->execute([$warehouse_id, $iid, $newBal]);
        }

        $stTxList->execute([$warehouse_id, $iid]);
        $running = 0.0;
        foreach ($stTxList->fetchAll(PDO::FETCH_ASSOC) as $tx) {
            $running += ((float)$tx['qty_in'] - (float)$tx['qty_out']);
            $stUpdTxBal->execute([$running, $tx['id']]);
        }
    }

    $pdo2->commit();

    recordInvActivityLog($pdo2, [
        'warehouse_id' => $warehouse_id,
        'user_fullname' => $actorName,
        'page_code' => 'receive',
        'page_name' => 'รับพัสดุเข้าคลัง',
        'action' => 'EDIT_RECEIPT',
        'target_type' => 'receipt',
        'target_id' => (string)$receipt_id,
        'details' => "แก้ไขใบรับเข้า {$rc['receipt_no']} (" . count($items) . " รายการ, ยอดรวม " . number_format($total_amount, 2) . " บาท)",
        'meta' => ['receipt_no' => $rc['receipt_no'], 'total_amount' => $total_amount, 'items_count' => count($items)]
    ], $pdo3 ?? null);

    echo json_encode([
        'status' => 'success',
        'message' => "แก้ไขใบรับเข้า {$rc['receipt_no']} และปรับปรุงสต๊อกเรียบร้อยแล้ว",
        'total_amount' => $total_amount
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

