<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';
require_once __DIR__ . '/inv_auth_helper.php';
require_once __DIR__ . '/inv_log_helper.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST method is allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$warehouse_id = isset($input['warehouse_id']) && $input['warehouse_id'] !== '' ? (int)$input['warehouse_id'] : 0;

try {
    $currentUser = getInvCurrentUser($pdo3 ?? null);
    if ((int)($currentUser['uid'] ?? 0) <= 0) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบใหม่อีกครั้ง']);
        exit;
    }

    $isAdmin = in_array('administrator', $currentUser['perms'] ?? [], true);
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'เฉพาะผู้ดูแลระบบ (Admin) เท่านั้นที่สามารถคำนวณยอดคงเหลือใหม่ได้']);
        exit;
    }

    $pdo2->beginTransaction();

    // 1. Sync inv_stock_balances with sum of inv_stock_lots per (warehouse_id, item_id)
    $whFilter = $warehouse_id > 0 ? "WHERE l.warehouse_id = ?" : "";
    $stLotSums = $pdo2->prepare("
        SELECT l.warehouse_id, l.item_id, COALESCE(SUM(l.balance_qty), 0) AS lot_sum
        FROM inv_stock_lots l
        {$whFilter}
        GROUP BY l.warehouse_id, l.item_id
    ");
    $stLotSums->execute($warehouse_id > 0 ? [$warehouse_id] : []);
    $lotRows = $stLotSums->fetchAll(PDO::FETCH_ASSOC);

    $lotMap = [];
    foreach ($lotRows as $lr) {
        $lotMap[$lr['warehouse_id'] . ':' . $lr['item_id']] = (float)$lr['lot_sum'];
    }

    $balWhere = $warehouse_id > 0 ? "WHERE warehouse_id = ?" : "";
    $stExistingBals = $pdo2->prepare("SELECT id, warehouse_id, item_id, balance_qty FROM inv_stock_balances {$balWhere}");
    $stExistingBals->execute($warehouse_id > 0 ? [$warehouse_id] : []);
    $existingBals = $stExistingBals->fetchAll(PDO::FETCH_ASSOC);

    $updBal = $pdo2->prepare("UPDATE inv_stock_balances SET balance_qty = ? WHERE id = ?");
    $insBal = $pdo2->prepare("
        INSERT INTO inv_stock_balances (warehouse_id, item_id, balance_qty)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE balance_qty = VALUES(balance_qty)
    ");

    $updatedBalances = 0;
    $seenKeys = [];
    foreach ($existingBals as $eb) {
        $key = $eb['warehouse_id'] . ':' . $eb['item_id'];
        $seenKeys[$key] = true;
        $targetQty = $lotMap[$key] ?? 0.0;
        if (abs((float)$eb['balance_qty'] - $targetQty) > 0.0001) {
            $updBal->execute([$targetQty, (int)$eb['id']]);
            $updatedBalances++;
        }
    }
    foreach ($lotRows as $lr) {
        $key = $lr['warehouse_id'] . ':' . $lr['item_id'];
        if (!isset($seenKeys[$key]) && (float)$lr['lot_sum'] > 0) {
            $insBal->execute([(int)$lr['warehouse_id'], (int)$lr['item_id'], (float)$lr['lot_sum']]);
            $updatedBalances++;
        }
    }

    // 2. Recalculate running balance_qty in inv_stock_transactions chronologically
    $txWhere = $warehouse_id > 0 ? "WHERE warehouse_id = ?" : "";
    $stPairs = $pdo2->prepare("SELECT DISTINCT warehouse_id, item_id FROM inv_stock_transactions {$txWhere}");
    $stPairs->execute($warehouse_id > 0 ? [$warehouse_id] : []);
    $pairs = $stPairs->fetchAll(PDO::FETCH_ASSOC);

    $stTxList = $pdo2->prepare("
        SELECT t.id, t.document_type, t.qty_in, t.qty_out, t.balance_qty, iss.status AS issue_status
        FROM inv_stock_transactions t
        LEFT JOIN inv_issues iss
          ON t.document_type IN ('ISSUE', 'CANCEL_ISSUE') AND t.document_id = iss.id
        WHERE t.warehouse_id = ? AND t.item_id = ?
        ORDER BY t.transaction_date ASC, t.id ASC
    ");
    $updTx = $pdo2->prepare("UPDATE inv_stock_transactions SET balance_qty = ? WHERE id = ?");

    $updatedTransactions = 0;
    foreach ($pairs as $pair) {
        $wId = (int)$pair['warehouse_id'];
        $iId = (int)$pair['item_id'];
        $stTxList->execute([$wId, $iId]);
        $txs = $stTxList->fetchAll(PDO::FETCH_ASSOC);

        $running = 0.0;
        foreach ($txs as $tx) {
            $isCancelledIssue = ($tx['document_type'] === 'CANCEL_ISSUE')
                || ($tx['document_type'] === 'ISSUE' && ($tx['issue_status'] ?? '') === 'CANCELLED');
            if (!$isCancelledIssue) {
                $running += (float)$tx['qty_in'] - (float)$tx['qty_out'];
            }
            if (abs((float)$tx['balance_qty'] - $running) > 0.0001) {
                $updTx->execute([$running, (int)$tx['id']]);
                $updatedTransactions++;
            }
        }
    }

    $pdo2->commit();

    recordInvActivityLog($pdo2, [
        'warehouse_id' => $warehouse_id > 0 ? $warehouse_id : null,
        'page_code' => 'requisitions',
        'page_name' => 'รายการเบิกวัสดุ',
        'action' => 'RECALCULATE_BALANCES',
        'target_type' => 'warehouse',
        'target_id' => $warehouse_id > 0 ? (string)$warehouse_id : 'all',
        'details' => "คำนวณยอดคงเหลือและบัญชีคุมใหม่ (ปรับยอดคลัง {$updatedBalances} รายการ, ปรับบัญชีคุม {$updatedTransactions} รายการ)",
        'meta' => [
            'updated_balances' => $updatedBalances,
            'updated_transactions' => $updatedTransactions,
        ],
    ], $pdo3 ?? null);

    echo json_encode([
        'status' => 'success',
        'message' => "คำนวณยอดคงเหลือใหม่เรียบร้อย (อัปเดตยอดคลัง {$updatedBalances} รายการ, ประวัติบัญชีคุม {$updatedTransactions} รายการ)",
        'updated_balances' => $updatedBalances,
        'updated_transactions' => $updatedTransactions,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

