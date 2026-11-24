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

$currentUser = getInvCurrentUser($pdo3 ?? null);
$perms = $currentUser['perms'] ?? [];
if (!in_array('administrator', $perms, true)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'เฉพาะผู้ดูแลระบบ (Admin) เท่านั้นที่สามารถรวมพัสดุได้']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$source_id = (int)($input['source_item_id'] ?? 0);
$target_id = (int)($input['target_item_id'] ?? 0);

if ($source_id <= 0 || $target_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุพัสดุต้นทางและพัสดุปลายทางให้ครบถ้วน']);
    exit;
}

if ($source_id === $target_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถรวมพัสดุรายการเดียวกันได้']);
    exit;
}

try {
    $pdo2->beginTransaction();

    $stmtItem = $pdo2->prepare("SELECT * FROM inv_items WHERE id = ? FOR UPDATE");
    $stmtItem->execute([$source_id]);
    $sourceItem = $stmtItem->fetch(PDO::FETCH_ASSOC);

    $stmtItem->execute([$target_id]);
    $targetItem = $stmtItem->fetch(PDO::FETCH_ASSOC);

    if (!$sourceItem || !$targetItem) {
        throw new Exception('ไม่พบข้อมูลพัสดุต้นทางหรือปลายทางในระบบ');
    }

    if ((int)$sourceItem['warehouse_id'] !== (int)$targetItem['warehouse_id']) {
        throw new Exception('สามารถรวมได้เฉพาะพัสดุที่อยู่ในคลังเดียวกันเท่านั้น');
    }

    $warehouse_id = (int)$targetItem['warehouse_id'];

    // 1. Move Lots (preserves each lot's lot_number, expiry_date, unit_cost, balance_qty)
    $stmtMoveLots = $pdo2->prepare("UPDATE inv_stock_lots SET item_id = ? WHERE item_id = ?");
    $stmtMoveLots->execute([$target_id, $source_id]);
    $movedLotsCount = $stmtMoveLots->rowCount();

    // 2. Move Stock Transactions & Document Line Items
    $tablesToUpdate = [
        'inv_stock_transactions',
        'inv_receipt_items',
        'inv_issue_items',
        'inv_requisition_items',
        'inv_purchase_request_items',
        'inv_purchase_order_items',
        'inv_transfer_items',
        'inv_return_items'
    ];
    foreach ($tablesToUpdate as $tbl) {
        $pdo2->prepare("UPDATE `$tbl` SET item_id = ? WHERE item_id = ?")->execute([$target_id, $source_id]);
    }

    // 3. Remove source balance row and recalculate target balance from inv_stock_lots
    $pdo2->prepare("DELETE FROM inv_stock_balances WHERE item_id = ?")->execute([$source_id]);

    $stmtLotSum = $pdo2->prepare("SELECT COALESCE(SUM(balance_qty), 0) FROM inv_stock_lots WHERE warehouse_id = ? AND item_id = ?");
    $stmtLotSum->execute([$warehouse_id, $target_id]);
    $newBalance = (float)$stmtLotSum->fetchColumn();

    $stmtCheckBal = $pdo2->prepare("SELECT id FROM inv_stock_balances WHERE warehouse_id = ? AND item_id = ? LIMIT 1");
    $stmtCheckBal->execute([$warehouse_id, $target_id]);
    $balId = $stmtCheckBal->fetchColumn();
    if ($balId) {
        $pdo2->prepare("UPDATE inv_stock_balances SET balance_qty = ? WHERE id = ?")->execute([$newBalance, $balId]);
    } else {
        $pdo2->prepare("INSERT INTO inv_stock_balances (warehouse_id, item_id, balance_qty) VALUES (?, ?, ?)")->execute([$warehouse_id, $target_id, $newBalance]);
    }

    // 4. Recalculate running balance_qty in inv_stock_transactions for Stock Card accuracy
    $stmtTxList = $pdo2->prepare("
        SELECT id, qty_in, qty_out
        FROM inv_stock_transactions
        WHERE warehouse_id = ? AND item_id = ?
        ORDER BY transaction_date ASC, id ASC
    ");
    $stmtTxList->execute([$warehouse_id, $target_id]);
    $stmtUpdTxBal = $pdo2->prepare("UPDATE inv_stock_transactions SET balance_qty = ? WHERE id = ?");
    $runningBal = 0.0;
    foreach ($stmtTxList->fetchAll(PDO::FETCH_ASSOC) as $tx) {
        $runningBal += ((float)$tx['qty_in'] - (float)$tx['qty_out']);
        $stmtUpdTxBal->execute([$runningBal, $tx['id']]);
    }

    // 5. Delete duplicate source item
    $pdo2->prepare("DELETE FROM inv_items WHERE id = ?")->execute([$source_id]);

    $pdo2->commit();

    $actorName = trim((string)($currentUser['fullname'] ?? '')) ?: 'ผู้ดูแลระบบ';
    $srcLabel = '[' . ($sourceItem['item_code'] ?: '-') . '] ' . ($sourceItem['item_name'] ?: '');
    $tgtLabel = '[' . ($targetItem['item_code'] ?: '-') . '] ' . ($targetItem['item_name'] ?: '');
    recordInvActivityLog($pdo2, [
        'warehouse_id' => $warehouse_id,
        'user_fullname' => $actorName,
        'page_code' => 'settings',
        'page_name' => 'ตั้งค่าระบบคลังวัสดุ',
        'action' => 'MERGE_ITEMS',
        'target_type' => 'item',
        'target_id' => (string)$target_id,
        'details' => "รวมพัสดุ {$srcLabel} เข้ากับ {$tgtLabel} (ย้าย {$movedLotsCount} Lot, คงเหลือรวม {$newBalance})",
        'meta' => [
            'source_item_id' => $source_id,
            'target_item_id' => $target_id,
            'moved_lots' => $movedLotsCount,
            'new_balance' => $newBalance
        ]
    ], $pdo3 ?? null);

    echo json_encode([
        'status' => 'success',
        'message' => "รวมพัสดุ {$srcLabel} เข้ากับ {$tgtLabel} สำเร็จเรียบร้อยแล้ว",
        'moved_lots' => $movedLotsCount,
        'new_balance' => $newBalance
    ]);
} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

