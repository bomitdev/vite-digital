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
$requisition_id = (int)($input['requisition_id'] ?? ($input['id'] ?? 0));
$cancel_reason = trim((string)($input['cancel_reason'] ?? ($input['reason'] ?? '')));

if ($requisition_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่พบรหัสใบขอเบิก']);
    exit;
}

try {
    $currentUser = getInvCurrentUser($pdo3 ?? null);
    if ((int)($currentUser['uid'] ?? 0) <= 0) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบใหม่อีกครั้ง']);
        exit;
    }

    $pdo2->beginTransaction();

    $stmtReq = $pdo2->prepare("SELECT * FROM inv_requisitions WHERE id = ? FOR UPDATE");
    $stmtReq->execute([$requisition_id]);
    $req = $stmtReq->fetch(PDO::FETCH_ASSOC);

    if (!$req) {
        throw new Exception('ไม่พบข้อมูลใบขอเบิก');
    }

    if ($req['status'] !== 'APPROVED') {
        throw new Exception('สามารถยกเลิกการจ่ายได้เฉพาะใบขอเบิกที่มีสถานะอนุมัติแล้วเท่านั้น');
    }

    $warehouse_id = (int)$req['warehouse_id'];
    $isAdmin = in_array('administrator', $currentUser['perms'] ?? [], true);
    if (!$isAdmin) {
        $pdo2->rollBack();
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'เฉพาะผู้ดูแลระบบ (Admin) เท่านั้นที่สามารถยกเลิกการจ่ายได้']);
        exit;
    }

    $actorName = trim((string)($currentUser['fullname'] ?? ''));
    if ($actorName === '') {
        $actorName = trim((string)($input['actor_name'] ?? 'ผู้ดูแลคลัง'));
    }

    $issue_id = (int)($req['issue_id'] ?? 0);
    $restoredCount = 0;

    if ($issue_id > 0) {
        $stmtIssueItems = $pdo2->prepare("
            SELECT item_id, lot_id, qty, unit_cost
            FROM inv_issue_items
            WHERE issue_id = ?
        ");
        $stmtIssueItems->execute([$issue_id]);
        $issueItems = $stmtIssueItems->fetchAll(PDO::FETCH_ASSOC);

        $stmtRestoreLot = $pdo2->prepare("UPDATE inv_stock_lots SET balance_qty = balance_qty + ? WHERE id = ?");
        $stmtRestoreBal = $pdo2->prepare("
            INSERT INTO inv_stock_balances (warehouse_id, item_id, balance_qty)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE balance_qty = balance_qty + VALUES(balance_qty)
        ");
        $stmtTx = $pdo2->prepare("
            INSERT INTO inv_stock_transactions
            (warehouse_id, item_id, lot_id, document_type, document_id, transaction_type, qty_in, unit_cost, balance_qty, transaction_date, user_id, user_name, remark)
            VALUES (?, ?, ?, 'CANCEL_ISSUE', ?, 'IN', ?, ?, (SELECT balance_qty FROM inv_stock_balances WHERE warehouse_id = ? AND item_id = ?), NOW(), ?, ?, ?)
        ");

        $txRemark = "ยกเลิกการจ่ายคำขอ " . $req['req_no'] . ($cancel_reason !== '' ? " ({$cancel_reason})" : '');

        foreach ($issueItems as $ii) {
            $itemId = (int)$ii['item_id'];
            $lotId = (int)($ii['lot_id'] ?? 0);
            $qty = (float)$ii['qty'];
            $unitCost = (float)($ii['unit_cost'] ?? 0);
            if ($qty <= 0 || $itemId <= 0) continue;

            if ($lotId > 0) {
                $stmtRestoreLot->execute([$qty, $lotId]);
            }
            $stmtRestoreBal->execute([$warehouse_id, $itemId, $qty]);
            $stmtTx->execute([
                $warehouse_id,
                $itemId,
                $lotId > 0 ? $lotId : null,
                $issue_id,
                $qty,
                $unitCost,
                $warehouse_id,
                $itemId,
                (int)$currentUser['uid'],
                $actorName,
                $txRemark
            ]);
            $restoredCount++;
        }

        $cancelNoteSuffix = " [ยกเลิกโดย {$actorName}" . ($cancel_reason !== '' ? ": {$cancel_reason}" : '') . "]";
        $stmtCancelIssue = $pdo2->prepare("
            UPDATE inv_issues
            SET status = 'CANCELLED', remark = CONCAT(COALESCE(remark, ''), ?)
            WHERE id = ?
        ");
        $stmtCancelIssue->execute([$cancelNoteSuffix, $issue_id]);
    }

    $newAdminNote = trim((string)($req['admin_note'] ?? ''));
    $cancelTag = "ยกเลิกการจ่ายโดย {$actorName}" . ($cancel_reason !== '' ? ": {$cancel_reason}" : '');
    $newAdminNote = $newAdminNote !== '' ? ($newAdminNote . " | " . $cancelTag) : $cancelTag;

    $stmtUpdReq = $pdo2->prepare("
        UPDATE inv_requisitions
        SET status = 'CANCELLED', admin_note = ?
        WHERE id = ?
    ");
    $stmtUpdReq->execute([$newAdminNote, $requisition_id]);

    $stmtUpdItems = $pdo2->prepare("
        UPDATE inv_requisition_items
        SET status = 'CANCELLED', qty_approved = 0
        WHERE requisition_id = ?
    ");
    $stmtUpdItems->execute([$requisition_id]);

    $pdo2->commit();

    recordInvActivityLog($pdo2, [
        'warehouse_id' => $warehouse_id,
        'page_code' => 'requisitions',
        'page_name' => 'รายการเบิกวัสดุ',
        'action' => 'CANCEL_APPROVED_REQUISITION',
        'target_type' => 'requisition',
        'target_id' => (string)$requisition_id,
        'details' => "ยกเลิกการจ่ายใบขอเบิก {$req['req_no']} และคืนสต๊อก {$restoredCount} รายการ" . ($cancel_reason !== '' ? " (เหตุผล: {$cancel_reason})" : ''),
        'meta' => [
            'req_no' => $req['req_no'],
            'issue_id' => $issue_id,
            'restored_items' => $restoredCount,
            'cancel_reason' => $cancel_reason,
        ],
    ], $pdo3 ?? null);

    echo json_encode([
        'status' => 'success',
        'message' => 'ยกเลิกการจ่ายและคืนยอดสต๊อกเรียบร้อยแล้ว',
        'restored_count' => $restoredCount,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

