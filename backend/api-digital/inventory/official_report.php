<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

// Ensure warehouse report settings columns exist
try {
    $colCheck1 = $pdo2->query("SHOW COLUMNS FROM inv_warehouses LIKE 'doc_no_prefix'");
    if (!$colCheck1 || !$colCheck1->fetch(PDO::FETCH_ASSOC)) {
        $pdo2->exec("ALTER TABLE inv_warehouses ADD COLUMN doc_no_prefix VARCHAR(100) NULL DEFAULT NULL");
    }
    $colCheck2 = $pdo2->query("SHOW COLUMNS FROM inv_warehouses LIKE 'report_signers_json'");
    if (!$colCheck2 || !$colCheck2->fetch(PDO::FETCH_ASSOC)) {
        $pdo2->exec("ALTER TABLE inv_warehouses ADD COLUMN report_signers_json TEXT NULL DEFAULT NULL");
    }
} catch (Exception $e) {
    // ignore if insufficient privileges
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $whId = (int)($input['warehouse_id'] ?? 0);
    $docNoPrefix = trim((string)($input['doc_no_prefix'] ?? ''));
    $signersInput = isset($input['signers']) && is_array($input['signers']) ? $input['signers'] : null;

    if ($whId <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบรหัสคลังวัสดุ']);
        exit;
    }

    try {
        if ($signersInput !== null) {
            if ($docNoPrefix === '' && isset($signersInput['doc_no_prefix'])) {
                $docNoPrefix = trim((string)$signersInput['doc_no_prefix']);
            }
            $signersJson = json_encode($signersInput, JSON_UNESCAPED_UNICODE);
            $stUpd = $pdo2->prepare("UPDATE inv_warehouses SET doc_no_prefix = ?, report_signers_json = ? WHERE id = ?");
            $stUpd->execute([$docNoPrefix !== '' ? $docNoPrefix : null, $signersJson, $whId]);
        } else {
            $stUpd = $pdo2->prepare("UPDATE inv_warehouses SET doc_no_prefix = ? WHERE id = ?");
            $stUpd->execute([$docNoPrefix !== '' ? $docNoPrefix : null, $whId]);
        }
        echo json_encode(['status' => 'success', 'message' => 'บันทึกเลขที่หนังสือและการตั้งค่าเรียบร้อยแล้ว']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

$warehouse_id = isset($_GET['warehouse_id']) && $_GET['warehouse_id'] !== '' ? (int)$_GET['warehouse_id'] : 1;
$report_mode = isset($_GET['report_mode']) ? trim($_GET['report_mode']) : 'monthly';
$category_id = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null;
$item_type_id = isset($_GET['item_type_id']) && $_GET['item_type_id'] !== '' ? (int)$_GET['item_type_id'] : null;

// Date calculations
if ($report_mode === 'yearly') {
    $start_date_raw = !empty($_GET['start_date']) ? trim($_GET['start_date']) : date('Y-01-01');
    $end_date_raw = !empty($_GET['end_date']) ? trim($_GET['end_date']) : date('Y-m-d');
    $start_date = $start_date_raw . ' 00:00:00';
    $end_date_str = $end_date_raw . ' 23:59:59';
    $month = null;
    $year = (int)date('Y', strtotime($start_date_raw));
} else {
    // Monthly mode
    $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
    $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
    if ($year > 2400) {
        $year -= 543; // Convert Buddhist Era if sent as BE
    }
    $start_date = sprintf('%04d-%02d-01 00:00:00', $year, $month);
    $end_date_str = date('Y-m-t 23:59:59', strtotime($start_date));
    $start_date_raw = date('Y-m-01', strtotime($start_date));
    $end_date_raw = date('Y-m-t', strtotime($start_date));
}

$thaiMonths = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
    5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
    9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
];

try {
    // 1. Warehouse Details
    $whStmt = $pdo2->prepare("SELECT * FROM inv_warehouses WHERE id = ?");
    $whStmt->execute([$warehouse_id]);
    $warehouse = $whStmt->fetch(PDO::FETCH_ASSOC);

    // Default signers according to warehouse
    $signers = [
        'dept_name' => 'กลุ่มงานสุขภาพดิจิทัล โรงพยาบาลชานุมาน',
        'doc_no_prefix' => 'อจ.0033.314/',
        'signer1_name' => 'นายสุริยา  จันทรา',
        'signer1_pos' => 'นักวิชาการคอมพิวเตอร์',
        'signer1_role' => 'ผู้สรุปรายงาน / เจ้าหน้าที่พัสดุ',
        'signer2_name' => 'นายศราวุฒิ  แสนโท',
        'signer2_pos' => 'นักวิชาการคอมพิวเตอร์ชำนาญการ',
        'signer2_title' => 'หัวหน้ากลุ่มงานสุขภาพดิจิทัล',
        'signer2_role' => 'หัวหน้ากลุ่มงาน / หัวหน้าพัสดุ',
        'signer3_name' => 'นายธนากร  คนเพียร',
        'signer3_pos' => 'นายแพทย์ชำนาญการ รักษาการในตำแหน่ง',
        'signer3_title' => 'ผู้อำนวยการโรงพยาบาลชานุมาน'
    ];

    if ($warehouse_id == 3 || ($warehouse && stripos($warehouse['name'], 'บริหาร') !== false)) {
        $signers = [
            'dept_name' => 'กลุ่มงานบริหารทั่วไป โรงพยาบาลชานุมาน',
            'doc_no_prefix' => 'อจ.0033.201/',
            'signer1_name' => 'นางภรภัค  อรอินทร์',
            'signer1_pos' => 'เจ้าพนักงานพัสดุ',
            'signer1_role' => 'ผู้สรุปรายงาน / เจ้าหน้าที่พัสดุ',
            'signer2_name' => 'นางสาวศิริลักษญ์  พรหมสาขา ณ สกลนคร',
            'signer2_pos' => 'นักวิชาการพัสดุ',
            'signer2_title' => 'หัวหน้างานพัสดุ',
            'signer2_role' => 'หัวหน้าพัสดุ',
            'signer3_name' => 'นายธนากร  คนเพียร',
            'signer3_pos' => 'นายแพทย์ชำนาญการ รักษาการในตำแหน่ง',
            'signer3_title' => 'ผู้อำนวยการโรงพยาบาลชานุมาน'
        ];
    }

    if ($warehouse && !empty($warehouse['report_signers_json'])) {
        $savedSigners = json_decode($warehouse['report_signers_json'], true);
        if (is_array($savedSigners)) {
            foreach ($savedSigners as $k => $v) {
                if (array_key_exists($k, $signers) && $v !== null && $v !== '') {
                    $signers[$k] = $v;
                }
            }
        }
    }
    if ($warehouse && !empty($warehouse['doc_no_prefix'])) {
        $signers['doc_no_prefix'] = $warehouse['doc_no_prefix'];
    }

    // 2. Fetch Items in this warehouse
    $itemSql = "
        SELECT i.id, i.item_code, i.item_name, i.item_name_en, i.standard_price, i.last_purchase_price,
               u.name as unit_name,
               c.name as category_name,
               COALESCE(b.balance_qty, 0) as current_balance
        FROM inv_items i
        LEFT JOIN inv_units u ON i.unit_id = u.id
        LEFT JOIN inv_categories c ON i.category_id = c.id
        LEFT JOIN inv_stock_balances b ON i.id = b.item_id AND b.warehouse_id = ?
        WHERE i.warehouse_id = ?
    ";
    $itemParams = [$warehouse_id, $warehouse_id];
    if ($category_id) {
        $itemSql .= " AND i.category_id = ?";
        $itemParams[] = $category_id;
    }
    if ($item_type_id) {
        $itemSql .= " AND i.item_type_id = ?";
        $itemParams[] = $item_type_id;
    }
    $itemSql .= " ORDER BY i.item_code ASC, i.item_name ASC";

    $itemStmt = $pdo2->prepare($itemSql);
    $itemStmt->execute($itemParams);
    $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

    $summary = [
        'forward_baht' => 0,
        'in_baht' => 0,
        'total_baht' => 0,
        'out_baht' => 0,
        'balance_baht' => 0
    ];
    $details = [];
    $all_depts = [];

    foreach ($items as $it) {
        $item_id = (int)$it['id'];
        $current_bal = (float)$it['current_balance'];
        $default_price = (float)($it['last_purchase_price'] > 0 ? $it['last_purchase_price'] : ($it['standard_price'] > 0 ? $it['standard_price'] : 0));

        // 3. Fetch Lots for this item in warehouse
        $lotStmt = $pdo2->prepare("
            SELECT id, lot_number, expiry_date, unit_cost, balance_qty
            FROM inv_stock_lots
            WHERE warehouse_id = ? AND item_id = ?
            ORDER BY CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END ASC, expiry_date ASC, id ASC
        ");
        $lotStmt->execute([$warehouse_id, $item_id]);
        $lots = $lotStmt->fetchAll(PDO::FETCH_ASSOC);

        // 4. Transactions in period
        $txStmt = $pdo2->prepare("
            SELECT t.*, 
                   COALESCE(iss.department_name, '') as resolved_dept,
                   COALESCE(v.vendor_name, '') as resolved_vendor
            FROM inv_stock_transactions t
            LEFT JOIN inv_issues iss ON t.document_type = 'ISSUE' AND t.document_id = iss.id
            LEFT JOIN inv_receipts r ON t.document_type IN ('RECEIVE', 'RECEIPT') AND t.document_id = r.id
            LEFT JOIN inv_vendors v ON r.vendor_id = v.id
            WHERE t.warehouse_id = ? AND t.item_id = ?
              AND t.transaction_date BETWEEN ? AND ?
            ORDER BY t.transaction_date ASC
        ");
        $txStmt->execute([$warehouse_id, $item_id, $start_date, $end_date_str]);
        $periodTxs = $txStmt->fetchAll(PDO::FETCH_ASSOC);

        // 5. Prior transactions before start_date grouped by lot_id (for exact ยอดยกมา)
        $priorStmt = $pdo2->prepare("
            SELECT COALESCE(lot_id, 0) as lot_id,
                COALESCE(SUM(qty_in - qty_out), 0) as prior_net
            FROM inv_stock_transactions
            WHERE warehouse_id = ? AND item_id = ? AND transaction_date < ?
            GROUP BY COALESCE(lot_id, 0)
        ");
        $priorStmt->execute([$warehouse_id, $item_id, $start_date]);
        $priorByLot = [];
        $total_prior_net = 0;
        $hasPriorTx = false;
        foreach ($priorStmt->fetchAll(PDO::FETCH_ASSOC) as $pr) {
            $lid = (int)$pr['lot_id'];
            $priorByLot[$lid] = (float)$pr['prior_net'];
            $total_prior_net += (float)$pr['prior_net'];
            $hasPriorTx = true;
        }

        // 6. Future transactions after end_date grouped by lot_id (only for legacy fallback without transactions)
        $futStmt = $pdo2->prepare("
            SELECT COALESCE(lot_id, 0) as lot_id,
                COALESCE(SUM(CASE WHEN transaction_type = 'IN' THEN qty_in ELSE 0 END), 0) as future_in,
                COALESCE(SUM(CASE WHEN transaction_type = 'OUT' THEN qty_out ELSE 0 END), 0) as future_out
            FROM inv_stock_transactions
            WHERE warehouse_id = ? AND item_id = ? AND transaction_date > ?
            GROUP BY COALESCE(lot_id, 0)
        ");
        $futStmt->execute([$warehouse_id, $item_id, $end_date_str]);
        $futureByLot = [];
        $total_future_in = 0;
        $total_future_out = 0;
        foreach ($futStmt->fetchAll(PDO::FETCH_ASSOC) as $fr) {
            $lid = (int)$fr['lot_id'];
            $futureByLot[$lid] = [
                'in' => (float)$fr['future_in'],
                'out' => (float)$fr['future_out']
            ];
            $total_future_in += (float)$fr['future_in'];
            $total_future_out += (float)$fr['future_out'];
        }

        if (count($lots) > 0) {
            // Split report rows by Lot so each lot uses its own unit_cost and balance
            $lotBuckets = [];
            foreach ($lots as $l) {
                $lid = (int)$l['id'];
                $lotPrice = (float)$l['unit_cost'] > 0 ? (float)$l['unit_cost'] : $default_price;
                $lotBuckets[$lid] = [
                    'lot_id' => $lid,
                    'lot_number' => trim((string)($l['lot_number'] ?? '')),
                    'expiry_date' => $l['expiry_date'] ?? null,
                    'price' => $lotPrice,
                    'current_bal' => (float)$l['balance_qty'],
                    'period_in' => 0,
                    'period_out' => 0,
                    'out_depts' => [],
                    'vendor' => ''
                ];
            }

            foreach ($periodTxs as $tx) {
                $txLotId = (int)($tx['lot_id'] ?? 0);
                if (!isset($lotBuckets[$txLotId])) {
                    $txPrice = (float)($tx['unit_cost'] ?? 0) > 0 ? (float)$tx['unit_cost'] : $default_price;
                    $lotBuckets[$txLotId] = [
                        'lot_id' => $txLotId,
                        'lot_number' => '',
                        'expiry_date' => null,
                        'price' => $txPrice,
                        'current_bal' => 0,
                        'period_in' => 0,
                        'period_out' => 0,
                        'out_depts' => [],
                        'vendor' => ''
                    ];
                }
                $qty_in = (float)$tx['qty_in'];
                $qty_out = (float)$tx['qty_out'];
                if ($tx['unit_cost'] > 0 && $lotBuckets[$txLotId]['price'] == 0) {
                    $lotBuckets[$txLotId]['price'] = (float)$tx['unit_cost'];
                }
                if ($tx['transaction_type'] === 'IN') {
                    $lotBuckets[$txLotId]['period_in'] += $qty_in;
                    if (!empty($tx['resolved_vendor'])) {
                        $lotBuckets[$txLotId]['vendor'] = $tx['resolved_vendor'];
                    }
                } elseif ($tx['transaction_type'] === 'OUT') {
                    $lotBuckets[$txLotId]['period_out'] += $qty_out;
                    $dept = trim($tx['resolved_dept'] ?: 'ไม่ระบุหน่วยงาน');
                    if (!isset($lotBuckets[$txLotId]['out_depts'][$dept])) {
                        $lotBuckets[$txLotId]['out_depts'][$dept] = 0;
                    }
                    $lotBuckets[$txLotId]['out_depts'][$dept] += $qty_out;
                }
            }

            // Ensure any lot_id present in priorByLot is also included
            foreach ($priorByLot as $pLid => $pNet) {
                if (!isset($lotBuckets[$pLid]) && $pNet != 0) {
                    $lotBuckets[$pLid] = [
                        'lot_id' => $pLid,
                        'lot_number' => '',
                        'expiry_date' => null,
                        'price' => $default_price,
                        'current_bal' => 0,
                        'period_in' => 0,
                        'period_out' => 0,
                        'out_depts' => [],
                        'vendor' => ''
                    ];
                }
            }

            $namedLotCount = count(array_filter($lotBuckets, fn($b) => $b['lot_number'] !== ''));
            $hasMultipleLots = count($lotBuckets) > 1 && $namedLotCount > 0;
            foreach ($lotBuckets as $lid => $lb) {
                $begin_bal = $priorByLot[$lid] ?? 0;
                $end_bal = $begin_bal + $lb['period_in'] - $lb['period_out'];

                if ($begin_bal > 0 || $lb['period_in'] > 0 || $lb['period_out'] > 0 || $end_bal > 0) {
                    $lotPrice = $lb['price'];
                    $forward_baht = $begin_bal * $lotPrice;
                    $in_baht = $lb['period_in'] * $lotPrice;
                    $out_baht = $lb['period_out'] * $lotPrice;
                    $balance_baht = $end_bal * $lotPrice;

                    $summary['forward_baht'] += $forward_baht;
                    $summary['in_baht'] += $in_baht;
                    $summary['out_baht'] += $out_baht;
                    $summary['balance_baht'] += $balance_baht;

                    foreach (array_keys($lb['out_depts']) as $dName) {
                        $all_depts[$dName] = true;
                    }

                    $displayName = $it['item_name'];
                    if ($lb['lot_number'] !== '' && $hasMultipleLots) {
                        $displayName .= " (Lot: {$lb['lot_number']})";
                    }

                    $details[] = [
                        'id' => $item_id,
                        'lot_id' => $lid,
                        'lot_number' => $lb['lot_number'],
                        'expiry_date' => $lb['expiry_date'],
                        'item_code' => $it['item_code'],
                        'name' => $displayName,
                        'unit' => $it['unit_name'] ?: 'หน่วย',
                        'price_per_unit' => $lotPrice,
                        'vendor' => $lb['vendor'],
                        'forward_qty' => $begin_bal,
                        'forward_baht' => $forward_baht,
                        'in_qty' => $lb['period_in'],
                        'in_baht' => $in_baht,
                        'total_qty' => $begin_bal + $lb['period_in'],
                        'out_qty' => $lb['period_out'],
                        'out_baht' => $out_baht,
                        'out_departments' => $lb['out_depts'],
                        'balance_qty' => $end_bal,
                        'balance_baht' => $balance_baht
                    ];
                }
            }
        } else {
            // Fallback for legacy items without lot records
            $price = $default_price;
            $period_in = 0;
            $period_out = 0;
            $out_depts = [];
            $vendor = '';

            foreach ($periodTxs as $tx) {
                $qty_in = (float)$tx['qty_in'];
                $qty_out = (float)$tx['qty_out'];
                if ($tx['unit_cost'] > 0 && $price == 0) {
                    $price = (float)$tx['unit_cost'];
                }

                if ($tx['transaction_type'] === 'IN') {
                    $period_in += $qty_in;
                    if (!empty($tx['resolved_vendor'])) {
                        $vendor = $tx['resolved_vendor'];
                    }
                } elseif ($tx['transaction_type'] === 'OUT') {
                    $period_out += $qty_out;
                    $dept = trim($tx['resolved_dept'] ?: 'ไม่ระบุหน่วยงาน');
                    if (!isset($out_depts[$dept])) {
                        $out_depts[$dept] = 0;
                    }
                    $out_depts[$dept] += $qty_out;
                    $all_depts[$dept] = true;
                }
            }

            if ($hasPriorTx || count($periodTxs) > 0) {
                $begin_bal = $total_prior_net;
                $end_bal = $begin_bal + $period_in - $period_out;
            } else {
                $end_bal = $current_bal - $total_future_in + $total_future_out;
                $begin_bal = $end_bal - $period_in + $period_out;
            }

            $forward_baht = $begin_bal * $price;
            $in_baht = $period_in * $price;
            $out_baht = $period_out * $price;
            $balance_baht = $end_bal * $price;

            $summary['forward_baht'] += $forward_baht;
            $summary['in_baht'] += $in_baht;
            $summary['out_baht'] += $out_baht;
            $summary['balance_baht'] += $balance_baht;

            if ($begin_bal > 0 || $period_in > 0 || $period_out > 0 || $end_bal > 0) {
                $details[] = [
                    'id' => $item_id,
                    'lot_id' => 0,
                    'lot_number' => '',
                    'expiry_date' => null,
                    'item_code' => $it['item_code'],
                    'name' => $it['item_name'],
                    'unit' => $it['unit_name'] ?: 'หน่วย',
                    'price_per_unit' => $price,
                    'vendor' => $vendor,
                    'forward_qty' => $begin_bal,
                    'forward_baht' => $forward_baht,
                    'in_qty' => $period_in,
                    'in_baht' => $in_baht,
                    'total_qty' => $begin_bal + $period_in,
                    'out_qty' => $period_out,
                    'out_baht' => $out_baht,
                    'out_departments' => $out_depts,
                    'balance_qty' => $end_bal,
                    'balance_baht' => $balance_baht
                ];
            }
        }
    }

    $summary['total_baht'] = $summary['forward_baht'] + $summary['in_baht'];

    $sortedDepts = array_keys($all_depts);
    sort($sortedDepts);

    echo json_encode([
        'status' => 'success',
        'report_mode' => $report_mode,
        'warehouse' => $warehouse,
        'signers' => $signers,
        'summary' => $summary,
        'departments' => $sortedDepts,
        'details' => $details,
        'period' => [
            'month' => $month,
            'year' => $year,
            'year_be' => $year + 543,
            'thai_month' => $month ? ($thaiMonths[$month] ?? '') : '',
            'start_date' => $start_date_raw,
            'end_date' => $end_date_raw,
            'formatted_start' => date('d/m/Y', strtotime($start_date_raw)),
            'formatted_end' => date('d/m/Y', strtotime($end_date_raw))
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

