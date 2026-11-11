<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$req_no = trim($_GET['req_no'] ?? '');

if ($id <= 0 && empty($req_no)) {
    echo json_encode(['status' => 'error', 'message' => 'ID or Requisition Number is required.']);
    exit;
}

try {
    if ($id > 0) {
        $stmt = $pdo2->prepare("
            SELECT r.*, w.name as warehouse_name, w.code as warehouse_code
            FROM inv_requisitions r
            LEFT JOIN inv_warehouses w ON r.warehouse_id = w.id
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo2->prepare("
            SELECT r.*, w.name as warehouse_name, w.code as warehouse_code
            FROM inv_requisitions r
            LEFT JOIN inv_warehouses w ON r.warehouse_id = w.id
            WHERE r.req_no = ?
        ");
        $stmt->execute([$req_no]);
    }

    $req = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$req) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลใบขอเบิก']);
        exit;
    }

    $warehouse_id = (int)$req['warehouse_id'];

    // Fetch items
    $stmtItems = $pdo2->prepare("
        SELECT 
            ri.*,
            COALESCE((
                SELECT SUM(b.balance_qty) 
                FROM inv_stock_balances b 
                WHERE b.item_id = ri.item_id AND b.warehouse_id = ?
            ), 0) as current_stock
        FROM inv_requisition_items ri
        WHERE ri.requisition_id = ?
        ORDER BY ri.id ASC
    ");
    $stmtItems->execute([$warehouse_id, $req['id']]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    // Fetch available lots for each item (FEFO)
    $stmtLots = $pdo2->prepare("
        SELECT id as lot_id, lot_number, expiry_date, unit_cost, balance_qty 
        FROM inv_stock_lots 
        WHERE item_id = ? AND warehouse_id = ? AND balance_qty > 0
        ORDER BY 
            CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END, 
            expiry_date ASC, 
            id ASC
    ");

    foreach ($items as &$it) {
        $stmtLots->execute([(int)$it['item_id'], $warehouse_id]);
        $lots = $stmtLots->fetchAll(PDO::FETCH_ASSOC);
        $it['available_lots'] = $lots;
        
        // Default approved qty to requested qty if pending
        if ($req['status'] === 'PENDING') {
            $maxCanApprove = min((float)$it['qty_requested'], (float)$it['current_stock']);
            $it['suggested_qty'] = $maxCanApprove > 0 ? $maxCanApprove : 0;
            // Pre-select first lot if available
            $it['selected_lot_id'] = !empty($lots) ? $lots[0]['lot_id'] : null;
        } else {
            $it['suggested_qty'] = (float)$it['qty_approved'];
            $it['selected_lot_id'] = $it['lot_id'];
        }
    }
    unset($it);

    // Fetch requester position from HR database if available
    $requester_position = '';
    if (isset($pdo3) && !empty($req['requester_name'])) {
        try {
            $cleanName = trim($req['requester_name']);
            $posSql = "
                SELECT 
                    CONCAT(pos.HR_POSITION_NAME, 
                        IF(hl.HR_LEVEL_NAME IS NOT NULL AND hl.HR_LEVEL_NAME != '', CONCAT(' ', hl.HR_LEVEL_NAME), '')
                    ) as position_name
                FROM hr_person p
                LEFT JOIN hr_prefix pfx ON p.HR_PREFIX_ID = pfx.HR_PREFIX_ID
                LEFT JOIN hr_position pos ON p.HR_POSITION_ID = pos.HR_POSITION_ID
                LEFT JOIN hr_level hl ON p.HR_LEVEL_ID = hl.HR_LEVEL_ID
                WHERE CONCAT(pfx.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) = :name
                   OR CONCAT(p.HR_FNAME, ' ', p.HR_LNAME) = :name2
                   OR REPLACE(CONCAT(pfx.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME), ' ', '') = REPLACE(:name3, ' ', '')
                LIMIT 1
            ";
            $posStmt = $pdo3->prepare($posSql);
            $posStmt->execute([':name' => $cleanName, ':name2' => $cleanName, ':name3' => $cleanName]);
            $requester_position = $posStmt->fetchColumn() ?: '';
        } catch (Exception $e) {}
    }
    $req['requester_position'] = $requester_position;

    // Formatted ID (e.g. 25690089)
    $dYear = (int)date('Y', strtotime($req['request_date'])) + 543;
    $req['formatted_id'] = $dYear . str_pad((string)$req['id'], 4, '0', STR_PAD_LEFT);

    // Global settings for signers
    $globalSettings = [];
    try {
        $stmtG = $pdo2->query("SELECT key_name, setting_value FROM mt_global_settings");
        while ($gRow = $stmtG->fetch(PDO::FETCH_ASSOC)) {
            $globalSettings[$gRow['key_name']] = $gRow['setting_value'];
        }
    } catch (Exception $e) {}
    $req['global_settings'] = $globalSettings;

    // Department signer
    $deptSigner = null;
    try {
        $stmtDS = $pdo2->prepare("
            SELECT * FROM mt_department_signers 
            WHERE TRIM(LOWER(department_name)) = TRIM(LOWER(?)) 
               OR ? LIKE CONCAT('%', TRIM(department_name), '%')
               OR TRIM(department_name) LIKE CONCAT('%', ?, '%')
            LIMIT 1
        ");
        $deptClean = trim($req['department_name']);
        $stmtDS->execute([$deptClean, $deptClean, $deptClean]);
        $deptSigner = $stmtDS->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
    $req['dept_signer'] = $deptSigner;

    $req['items'] = $items;

    echo json_encode([
        'status' => 'success',
        'data' => $req
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

