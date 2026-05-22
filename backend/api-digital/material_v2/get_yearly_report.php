<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-01-01');
$end_date_str = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Ensure time boundaries
$start_date .= " 00:00:00";
$end_date_str .= " 23:59:59";

try {
    // Select all admin materials
    $stmt = $pdo2->prepare("
        SELECT id, name, type, unit, balance as current_balance, price_per_unit 
        FROM mt_materials 
    ");
    $stmt->execute();
    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $summary = [
        'forward_baht' => 0,
        'in_baht' => 0,
        'out_baht' => 0,
        'balance_baht' => 0,
        'total_baht' => 0
    ];

    $details = [];

    foreach ($materials as $mat) {
        $price = floatval($mat['price_per_unit']);
        $mat_id = $mat['id'];

        // Get transactions during the year
        $stmtYear = $pdo2->prepare("
            SELECT action_type, quantity, reference_dest
            FROM mt_transactions 
            WHERE material_id = :id AND action_date >= :start_date AND action_date <= :end_date
        ");
        $stmtYear->execute([':id' => $mat_id, ':start_date' => $start_date, ':end_date' => $end_date_str]);
        $yearlyTransactions = $stmtYear->fetchAll(PDO::FETCH_ASSOC);

        $year_in = 0;
        $year_out = 0;
        $out_depts = [];

        foreach ($yearlyTransactions as $tx) {
            $qty = intval($tx['quantity']);
            if ($tx['action_type'] === 'IN') {
                $year_in += $qty;
            } else if ($tx['action_type'] === 'OUT') {
                $year_out += $qty;
                $dept = trim($tx['reference_dest']);
                if (!isset($out_depts[$dept])) {
                    $out_depts[$dept] = 0;
                }
                $out_depts[$dept] += $qty;
            }
        }

        // Get past transactions to calculate correct beginning balance (before this year)
        $stmtPast = $pdo2->prepare("
            SELECT 
                SUM(CASE WHEN action_type = 'IN' THEN quantity ELSE 0 END) as past_in,
                SUM(CASE WHEN action_type = 'OUT' THEN quantity ELSE 0 END) as past_out
            FROM mt_transactions 
            WHERE material_id = :id AND action_date < :start_date
        ");
        $stmtPast->execute([':id' => $mat_id, ':start_date' => $start_date]);
        $pastTx = $stmtPast->fetch(PDO::FETCH_ASSOC);
        $past_in = intval($pastTx['past_in'] ?? 0);
        $past_out = intval($pastTx['past_out'] ?? 0);

        // Get latest vendor
        $stmtVendor = $pdo2->prepare("
            SELECT reference_dest 
            FROM mt_transactions 
            WHERE material_id = :id AND action_type = 'IN' AND action_date <= :end_date
            ORDER BY action_date DESC LIMIT 1
        ");
        $stmtVendor->execute([':id' => $mat_id, ':end_date' => $end_date_str]);
        $vendorRow = $stmtVendor->fetch(PDO::FETCH_ASSOC);
        $vendor = $vendorRow ? $vendorRow['reference_dest'] : '';

        // Calculate balances forward from past transactions
        $begin_bal = $past_in - $past_out;
        if ($begin_bal < 0) $begin_bal = 0; // Prevent negative stock from bad manual data

        $end_bal = $begin_bal + $year_in - $year_out;

        // Apply prices
        $forward_baht = $begin_bal * $price;
        $in_baht = $year_in * $price;
        $out_baht = $year_out * $price;
        $balance_baht = $end_bal * $price;

        $summary['forward_baht'] += $forward_baht;
        $summary['in_baht'] += $in_baht;
        $summary['out_baht'] += $out_baht;
        $summary['balance_baht'] += $balance_baht;

        if ($begin_bal > 0 || $year_in > 0 || $year_out > 0 || $end_bal > 0) {
            $details[] = [
                'id' => $mat['id'],
                'name' => $mat['name'],
                'unit' => $mat['unit'],
                'price_per_unit' => $price,
                'vendor' => $vendor,
                'forward_qty' => $begin_bal,
                'forward_baht' => $forward_baht,
                'in_qty' => $year_in,
                'in_baht' => $in_baht,
                'total_qty' => $begin_bal + $year_in,
                'out_qty' => $year_out,
                'out_baht' => $out_baht,
                'out_departments' => $out_depts,
                'balance_qty' => $end_bal,
                'balance_baht' => $balance_baht
            ];
        }
    }

    $summary['total_baht'] = $summary['forward_baht'] + $summary['in_baht'];

    echo json_encode([
        'status' => 'success',
        'data' => $summary,
        'details' => $details,
        'period' => [
            'start' => $start_date,
            'end' => $end_date_str
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'DB Error: ' . $e->getMessage()
    ]);
}
