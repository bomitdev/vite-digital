<?php
// backend/api-digital/it_service/printer_report.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'summary') {
    // We want to calculate:
    // 1. Pages printed THIS month (Current total - Last month's total)
    // 2. Pages printed THIS fiscal year (Current total - Total at end of last September)
    
    // First, let's get all printers
    $stmt = $pdo2->query("SELECT id, name, ip_address FROM it_printers ORDER BY id ASC");
    $printers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $results = [];
    
    // Determine last month's last day (for monthly baseline)
    $last_month_end = date('Y-m-d', strtotime('last day of previous month'));
    
    // Determine last fiscal year end (September 30)
    // If current month is Oct, Nov, Dec, fiscal year started this year Sep 30.
    // If Jan-Sep, fiscal year started last year Sep 30.
    $current_month = (int)date('m');
    $current_year = (int)date('Y');
    
    if ($current_month >= 10) {
        $last_fy_end = $current_year . '-09-30';
    } else {
        $last_fy_end = ($current_year - 1) . '-09-30';
    }

    foreach ($printers as $p) {
        $pid = $p['id'];
        
        // Get current (latest) log
        $stmt_curr = $pdo2->prepare("SELECT page_count, record_date FROM it_printer_logs WHERE printer_id = ? ORDER BY record_date DESC LIMIT 1");
        $stmt_curr->execute([$pid]);
        $current = $stmt_curr->fetch(PDO::FETCH_ASSOC);
        
        $current_count = $current ? (int)$current['page_count'] : 0;
        
        // Get last month baseline
        // We look for the log closest to, but not exceeding, the end of last month
        $stmt_lm = $pdo2->prepare("SELECT page_count FROM it_printer_logs WHERE printer_id = ? AND record_date <= ? ORDER BY record_date DESC LIMIT 1");
        $stmt_lm->execute([$pid, $last_month_end]);
        $lm = $stmt_lm->fetch(PDO::FETCH_ASSOC);
        $lm_count = $lm ? (int)$lm['page_count'] : $current_count; // if no history, delta is 0
        
        // Get last FY baseline
        $stmt_fy = $pdo2->prepare("SELECT page_count FROM it_printer_logs WHERE printer_id = ? AND record_date <= ? ORDER BY record_date DESC LIMIT 1");
        $stmt_fy->execute([$pid, $last_fy_end]);
        $fy = $stmt_fy->fetch(PDO::FETCH_ASSOC);
        $fy_count = $fy ? (int)$fy['page_count'] : $current_count;

        // Determine yesterday
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        // Get yesterday baseline (the most recent log before today)
        $stmt_yd = $pdo2->prepare("SELECT page_count FROM it_printer_logs WHERE printer_id = ? AND record_date <= ? ORDER BY record_date DESC LIMIT 1");
        $stmt_yd->execute([$pid, $yesterday]);
        $yd = $stmt_yd->fetch(PDO::FETCH_ASSOC);
        $yd_count = $yd ? (int)$yd['page_count'] : $current_count;
        
        $today_usage = max(0, $current_count - $yd_count);
        $this_month_usage = max(0, $current_count - $lm_count);
        $this_fy_usage = max(0, $current_count - $fy_count);
        
        $results[] = [
            'id' => $pid,
            'name' => $p['name'],
            'ip' => $p['ip_address'],
            'current_total' => $current_count,
            'today_usage' => $today_usage,
            'this_month_usage' => $this_month_usage,
            'this_fy_usage' => $this_fy_usage
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $results, 'debug' => ['last_month_end' => $last_month_end, 'last_fy_end' => $last_fy_end]]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
