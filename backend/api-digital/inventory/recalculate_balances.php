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

    // 1. Replay transactions chronologically per (warehouse_id, item_id)
    //    Re-allocate active ISSUEs in FEFO/FIFO order, sync lot balances, and recalculate running balance_qty
    $txWhere = $warehouse_id > 0 ? "WHERE warehouse_id = ?" : "";
    $stPairs = $pdo2->prepare("SELECT DISTINCT warehouse_id, item_id FROM inv_stock_transactions {$txWhere}");
    $stPairs->execute($warehouse_id > 0 ? [$warehouse_id] : []);
    $pairs = $stPairs->fetchAll(PDO::FETCH_ASSOC);

    $stLotsForItem = $pdo2->prepare("
        SELECT l.id, l.expiry_date, l.unit_cost, l.receive_qty, l.balance_qty, l.created_at,
               COALESCE((SELECT MIN(t.transaction_date) FROM inv_stock_transactions t WHERE t.lot_id = l.id AND t.transaction_type = 'IN' AND t.document_type != 'CANCEL_ISSUE'), l.created_at) AS first_in_date
        FROM inv_stock_lots l
        WHERE l.warehouse_id = ? AND l.item_id = ?
    ");

    $stTxList = $pdo2->prepare("
        SELECT t.id, t.lot_id, t.document_type, t.document_id, t.transaction_type, t.qty_in, t.qty_out, t.unit_cost, t.balance_qty, iss.status AS issue_status
        FROM inv_stock_transactions t
        LEFT JOIN inv_issues iss
          ON t.document_type IN ('ISSUE', 'CANCEL_ISSUE') AND t.document_id = iss.id
        WHERE t.warehouse_id = ? AND t.item_id = ?
        ORDER BY t.transaction_date ASC, t.id ASC
    ");

    $updTxBal = $pdo2->prepare("UPDATE inv_stock_transactions SET balance_qty = ? WHERE id = ?");
    $updTxLot = $pdo2->prepare("UPDATE inv_stock_transactions SET lot_id = ?, unit_cost = ?, balance_qty = ? WHERE id = ?");
    $updLotBal = $pdo2->prepare("UPDATE inv_stock_lots SET balance_qty = ? WHERE id = ?");
    $updIssueItem = $pdo2->prepare("UPDATE inv_issue_items SET lot_id = ?, unit_cost = ?, total_price = qty * ? WHERE issue_id = ? AND item_id = ? AND lot_id = ? LIMIT 1");
    $updReqItem = $pdo2->prepare("UPDATE inv_requisition_items ri JOIN inv_requisitions r ON ri.requisition_id = r.id SET ri.lot_id = ? WHERE r.issue_id = ? AND ri.item_id = ? AND ri.lot_id = ?");

    $updatedTransactions = 0;
    $reallocatedLots = 0;
    $touchedIssueIds = [];

    foreach ($pairs as $pair) {
        $wId = (int)$pair['warehouse_id'];
        $iId = (int)$pair['item_id'];

        $stLotsForItem->execute([$wId, $iId]);
        $lotPool = [];
        foreach ($stLotsForItem->fetchAll(PDO::FETCH_ASSOC) as $lr) {
            $lid = (int)$lr['id'];
            $lotPool[$lid] = [
                'id' => $lid,
                'expiry_date' => $lr['expiry_date'],
                'first_in_date' => $lr['first_in_date'],
                'unit_cost' => (float)$lr['unit_cost'],
                'old_balance' => (float)$lr['balance_qty'],
                'available' => 0.0,
            ];
        }

        $stTxList->execute([$wId, $iId]);
        $txs = $stTxList->fetchAll(PDO::FETCH_ASSOC);

        $running = 0.0;
        foreach ($txs as $tx) {
            $docType = (string)$tx['document_type'];
            $txType = (string)$tx['transaction_type'];
            $isCancelledIssue = ($docType === 'CANCEL_ISSUE')
                || ($docType === 'ISSUE' && ($tx['issue_status'] ?? '') === 'CANCELLED');

            if ($isCancelledIssue) {
                if (abs((float)$tx['balance_qty'] - $running) > 0.0001) {
                    $updTxBal->execute([$running, (int)$tx['id']]);
                    $updatedTransactions++;
                }
                continue;
            }

            $oldLotId = (int)($tx['lot_id'] ?? 0);
            if ($txType === 'IN') {
                $qtyIn = (float)$tx['qty_in'];
                $running += $qtyIn;
                if ($oldLotId > 0 && isset($lotPool[$oldLotId])) {
                    $lotPool[$oldLotId]['available'] += $qtyIn;
                }
                if (abs((float)$tx['balance_qty'] - $running) > 0.0001) {
                    $updTxBal->execute([$running, (int)$tx['id']]);
                    $updatedTransactions++;
                }
                continue;
            }

            // Active OUT transaction
            $qtyOut = (float)$tx['qty_out'];
            $running -= $qtyOut;

            // Find best FEFO/FIFO lot currently available in $lotPool
            $candidates = array_filter($lotPool, function ($l) {
                return $l['available'] > 0.0001;
            });
            usort($candidates, function ($a, $b) {
                $aHasExp = !empty($a['expiry_date']) ? 0 : 1;
                $bHasExp = !empty($b['expiry_date']) ? 0 : 1;
                if ($aHasExp !== $bHasExp) return $aHasExp <=> $bHasExp;
                if ($aHasExp === 0 && $a['expiry_date'] !== $b['expiry_date']) {
                    return strcmp((string)$a['expiry_date'], (string)$b['expiry_date']);
                }
                $cmpDate = strcmp((string)$a['first_in_date'], (string)$b['first_in_date']);
                if ($cmpDate !== 0) return $cmpDate;
                return $a['id'] <=> $b['id'];
            });

            $chosenLotId = $oldLotId;
            if (!empty($candidates)) {
                $firstLot = $candidates[0];
                if ($firstLot['available'] + 0.0001 >= $qtyOut) {
                    $chosenLotId = $firstLot['id'];
                } elseif ($oldLotId > 0 && isset($lotPool[$oldLotId]) && $lotPool[$oldLotId]['available'] + 0.0001 >= $qtyOut) {
                    $chosenLotId = $oldLotId;
                }
            }

            if ($chosenLotId > 0 && isset($lotPool[$chosenLotId])) {
                $lotPool[$chosenLotId]['available'] = max(0.0, $lotPool[$chosenLotId]['available'] - $qtyOut);
                $newUnitCost = $lotPool[$chosenLotId]['unit_cost'];
            } else {
                $newUnitCost = (float)$tx['unit_cost'];
            }

            if ($chosenLotId > 0 && $chosenLotId !== $oldLotId) {
                $updTxLot->execute([$chosenLotId, $newUnitCost, $running, (int)$tx['id']]);
                if ($docType === 'ISSUE' && !empty($tx['document_id'])) {
                    $issueId = (int)$tx['document_id'];
                    $updIssueItem->execute([$chosenLotId, $newUnitCost, $newUnitCost, $issueId, $iId, $oldLotId]);
                    $updReqItem->execute([$chosenLotId, $issueId, $iId, $oldLotId]);
                    $touchedIssueIds[$issueId] = true;
                }
                $reallocatedLots++;
                $updatedTransactions++;
            } elseif (abs((float)$tx['balance_qty'] - $running) > 0.0001) {
                $updTxBal->execute([$running, (int)$tx['id']]);
                $updatedTransactions++;
            }
        }

        // Persist updated lot balances if changed
        foreach ($lotPool as $lot) {
            if (abs($lot['old_balance'] - $lot['available']) > 0.0001) {
                $updLotBal->execute([$lot['available'], $lot['id']]);
            }
        }
    }

    // Recompute total_amount on touched inv_issues
    if (!empty($touchedIssueIds)) {
        $updIssueTotal = $pdo2->prepare("
            UPDATE inv_issues
            SET total_amount = COALESCE((SELECT SUM(total_price) FROM inv_issue_items WHERE issue_id = ?), 0)
            WHERE id = ?
        ");
        foreach (array_keys($touchedIssueIds) as $issId) {
            $updIssueTotal->execute([$issId, $issId]);
        }
    }

    // 2. Sync inv_stock_balances with sum of inv_stock_lots per (warehouse_id, item_id)
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

    $pdo2->commit();

    recordInvActivityLog($pdo2, [
        'warehouse_id' => $warehouse_id > 0 ? $warehouse_id : null,
        'page_code' => 'requisitions',
        'page_name' => 'รายการเบิกวัสดุ',
        'action' => 'RECALCULATE_BALANCES',
        'target_type' => 'warehouse',
        'target_id' => $warehouse_id > 0 ? (string)$warehouse_id : 'all',
        'details' => "คำนวณยอดคงเหลือ จัดสรร Lot FEFO/FIFO และบัญชีคุมใหม่ (จัดสรร Lot ใหม่ {$reallocatedLots} รายการ, ปรับยอดคลัง {$updatedBalances} รายการ, ปรับบัญชีคุม {$updatedTransactions} รายการ)",
        'meta' => [
            'reallocated_lots' => $reallocatedLots,
            'updated_balances' => $updatedBalances,
            'updated_transactions' => $updatedTransactions,
        ],
    ], $pdo3 ?? null);

    echo json_encode([
        'status' => 'success',
        'message' => "คำนวณยอดคงเหลือใหม่เรียบร้อย (จัดสรร Lot ใหม่ {$reallocatedLots} รายการ, อัปเดตยอดคลัง {$updatedBalances} รายการ, บัญชีคุม {$updatedTransactions} รายการ)",
        'reallocated_lots' => $reallocatedLots,
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
