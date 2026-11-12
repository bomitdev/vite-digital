<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';
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
$approver_name = trim($input['approver_name'] ?? 'ผู้ดูแลระบบ');
$admin_note = trim($input['admin_note'] ?? '');
$items = $input['items'] ?? [];

if ($requisition_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่พบรหัสใบขอเบิก']);
    exit;
}

try {
    $pdo2->beginTransaction();

    // 1. Fetch requisition
    $stmtReq = $pdo2->prepare("SELECT * FROM inv_requisitions WHERE id = ? FOR UPDATE");
    $stmtReq->execute([$requisition_id]);
    $req = $stmtReq->fetch(PDO::FETCH_ASSOC);

    if (!$req) {
        throw new Exception("ไม่พบข้อมูลใบขอเบิกเลขที่นี้");
    }

    if ($req['status'] !== 'PENDING') {
        throw new Exception("ใบขอเบิกนี้ถูกดำเนินการไปแล้ว (สถานะปัจจุบัน: " . $req['status'] . ")");
    }

    $warehouse_id = (int)$req['warehouse_id'];

    // 2. Validate items to approve
    $total_qty_to_issue = 0;
    $items_to_issue = [];

    // Prepared statements for lots and balances
    $stmtLotCheck = $pdo2->prepare("SELECT balance_qty, unit_cost, lot_number FROM inv_stock_lots WHERE id = ? AND warehouse_id = ? FOR UPDATE");
    $stmtFefoLots = $pdo2->prepare("
        SELECT id, balance_qty, unit_cost, lot_number 
        FROM inv_stock_lots 
        WHERE item_id = ? AND warehouse_id = ? AND balance_qty > 0 
        ORDER BY 
            CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END, 
            expiry_date ASC, 
            id ASC 
        FOR UPDATE
    ");

    foreach ($items as $itemInput) {
        $row_id = (int)($itemInput['id'] ?? 0);
        $item_id = (int)($itemInput['item_id'] ?? 0);
        $qty_approved = (float)($itemInput['qty_approved'] ?? 0);
        $lot_id = !empty($itemInput['lot_id']) ? (int)$itemInput['lot_id'] : 0;

        if ($qty_approved <= 0) {
            // Updated item as not approved/0
            $stmtZero = $pdo2->prepare("UPDATE inv_requisition_items SET qty_approved = 0, status = 'REJECTED' WHERE id = ?");
            $stmtZero->execute([$row_id]);
            continue;
        }

        // If lot_id specified, check it
        if ($lot_id > 0) {
            $stmtLotCheck->execute([$lot_id, $warehouse_id]);
            $lot = $stmtLotCheck->fetch(PDO::FETCH_ASSOC);
            if (!$lot || (float)$lot['balance_qty'] < $qty_approved) {
                throw new Exception("สต๊อกใน Lot ID $lot_id ไม่เพียงพอ (คงเหลือ: " . ($lot['balance_qty'] ?? 0) . ", ต้องการจ่าย: $qty_approved)");
            }
            $items_to_issue[] = [
                'row_id' => $row_id,
                'item_id' => $item_id,
                'lot_id' => $lot_id,
                'qty' => $qty_approved,
                'unit_cost' => (float)$lot['unit_cost']
            ];
            $total_qty_to_issue += $qty_approved;
        } else {
            // Auto pick FEFO lot
            $stmtFefoLots->execute([$item_id, $warehouse_id]);
            $availLots = $stmtFefoLots->fetchAll(PDO::FETCH_ASSOC);
            if (empty($availLots)) {
                throw new Exception("ไม่มีสินค้าในสต๊อกสำหรับรายการ ID $item_id");
            }

            $remQty = $qty_approved;
            foreach ($availLots as $l) {
                if ($remQty <= 0) break;
                $takeQty = min($remQty, (float)$l['balance_qty']);
                $items_to_issue[] = [
                    'row_id' => $row_id,
                    'item_id' => $item_id,
                    'lot_id' => (int)$l['id'],
                    'qty' => $takeQty,
                    'unit_cost' => (float)$l['unit_cost']
                ];
                $remQty -= $takeQty;
            }

            if ($remQty > 0) {
                throw new Exception("ยอดสต๊อกคงเหลือรวมในคลังไม่พอสำหรับรายการ ID $item_id (ขาดอีก $remQty)");
            }
            $total_qty_to_issue += $qty_approved;
        }
    }

    if ($total_qty_to_issue <= 0) {
        throw new Exception("ไม่มีรายการที่ได้รับการอนุมัติจ่าย (จำนวนอนุมัติรวมต้องมากกว่า 0)");
    }

    // 3. Create Stock Issue Document (inv_issues)
    $issue_no = 'IS-' . date('ymd') . '-' . rand(1000, 9999);
    $total_amount = 0;
    foreach ($items_to_issue as $ii) {
        $total_amount += ($ii['qty'] * $ii['unit_cost']);
    }

    $stmtIssue = $pdo2->prepare("
        INSERT INTO inv_issues 
        (issue_no, issue_date, warehouse_id, department_id, department_name, total_amount, requester_name, issuer_name, remark, status) 
        VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, 'COMPLETED')
    ");
    $issueRemark = "อนุมัติจ่ายตามใบขอเบิกเลขที่: " . $req['req_no'] . ($admin_note ? " ($admin_note)" : "");
    $stmtIssue->execute([
        $issue_no,
        $warehouse_id,
        $req['department_id'],
        $req['department_name'],
        $total_amount,
        $req['requester_name'],
        $approver_name,
        $issueRemark
    ]);
    $issue_id = (int)$pdo2->lastInsertId();

    // 4. Deduct lots, balances, insert issue items & transactions
    $stmtIssueItem = $pdo2->prepare("
        INSERT INTO inv_issue_items (issue_id, item_id, lot_id, qty, unit_id, unit_cost, total_price)
        VALUES (?, ?, ?, ?, (SELECT unit_id FROM inv_items WHERE id = ?), ?, ?)
    ");
    $stmtDeductLot = $pdo2->prepare("UPDATE inv_stock_lots SET balance_qty = balance_qty - ? WHERE id = ?");
    $stmtDeductBal = $pdo2->prepare("UPDATE inv_stock_balances SET balance_qty = balance_qty - ? WHERE warehouse_id = ? AND item_id = ?");
    $stmtTx = $pdo2->prepare("
        INSERT INTO inv_stock_transactions 
        (warehouse_id, item_id, lot_id, document_type, document_id, transaction_type, qty_out, unit_cost, balance_qty, transaction_date, user_name, remark)
        VALUES (?, ?, ?, 'ISSUE', ?, 'OUT', ?, ?, (SELECT balance_qty FROM inv_stock_balances WHERE warehouse_id = ? AND item_id = ?), NOW(), ?, ?)
    ");

    $stmtUpdateReqItem = $pdo2->prepare("
        UPDATE inv_requisition_items 
        SET qty_approved = ?, lot_id = ?, status = 'APPROVED' 
        WHERE id = ?
    ");

    foreach ($items_to_issue as $it) {
        $lotPrice = $it['qty'] * $it['unit_cost'];
        
        // Insert issue item
        $stmtIssueItem->execute([
            $issue_id, $it['item_id'], $it['lot_id'], $it['qty'], $it['item_id'], $it['unit_cost'], $lotPrice
        ]);

        // Deduct lot
        $stmtDeductLot->execute([$it['qty'], $it['lot_id']]);

        // Deduct warehouse balance
        $stmtDeductBal->execute([$it['qty'], $warehouse_id, $it['item_id']]);

        // Stock transaction record
        $stmtTx->execute([
            $warehouse_id, $it['item_id'], $it['lot_id'], $issue_id, $it['qty'], $it['unit_cost'],
            $warehouse_id, $it['item_id'], $approver_name, "อนุมัติจ่ายคำขอ " . $req['req_no']
        ]);

        // Update requisition item
        if (!empty($it['row_id'])) {
            $stmtUpdateReqItem->execute([$it['qty'], $it['lot_id'], $it['row_id']]);
        }
    }

    // 5. Update requisition header
    $stmtUpReq = $pdo2->prepare("
        UPDATE inv_requisitions 
        SET status = 'APPROVED', approver_name = ?, approved_date = NOW(), admin_note = ?, issue_id = ? 
        WHERE id = ?
    ");
    $stmtUpReq->execute([$approver_name, $admin_note, $issue_id, $requisition_id]);

    $pdo2->commit();

    recordInvActivityLog($pdo2, [
        'warehouse_id' => $warehouse_id,
        'user_fullname' => $approver_name,
        'page_code' => 'requisitions',
        'page_name' => 'รายการเบิกวัสดุ',
        'action' => 'APPROVE_REQUISITION',
        'target_type' => 'requisition',
        'target_id' => (string)$requisition_id,
        'details' => "อนุมัติใบขอเบิก {$req['req_no']} และออกใบจ่าย {$issue_no} จำนวนรวม {$total_qty_to_issue} หน่วย",
        'meta' => ['req_no' => $req['req_no'], 'issue_no' => $issue_no, 'issue_id' => $issue_id, 'total_qty' => $total_qty_to_issue],
    ], $pdo3 ?? null);

    echo json_encode([
        'status' => 'success',
        'message' => 'อนุมัติคำขอเบิกและตัดจ่ายสต๊อกสำเร็จเรียบร้อยแล้ว',
        'issue_no' => $issue_no,
        'issue_id' => $issue_id
    ]);
} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

