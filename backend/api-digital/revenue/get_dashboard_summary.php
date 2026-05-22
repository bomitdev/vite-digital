<?php
require_once '../../config.php';
require_once '../../auth_utils.php';

$fiscal_year = $_GET['fiscal_year'] ?? (date('Y') + 543 + (date('m') >= 10 ? 1 : 0)); // Thai Fiscal Year Logic
$filter_target_id = $_GET['target_id'] ?? '';

$userData = authOptional();
$isAdmin = false;
$fullname = '';

if ($userData) {
    try {
        $stmtUser = $pdo3->prepare("
            SELECT 
                CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as FULLNAME,
                hds.HR_DEPARTMENT_SUB_NAME
            FROM hr_person p
            LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
            LEFT JOIN hr_department_sub hds ON p.HR_DEPARTMENT_SUB_ID = hds.HR_DEPARTMENT_SUB_ID
            WHERE p.ID = ?
        ");
        $stmtUser->execute([$userData['uid']]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $fullname = trim($user['FULLNAME']);
            $dept = $user['HR_DEPARTMENT_SUB_NAME'] ?? '';
            if (strpos($dept, 'สุขภาพดิจิทัล') !== false || strpos($dept, 'บริหาร') !== false || strpos($dept, 'ประกัน') !== false) {
                $isAdmin = true;
            }
        }
    } catch (PDOException $e) {
        // Fallback to non-admin
    }
}

$whereClause = "WHERE t.fiscal_year = ?";
$params = [$fiscal_year];

if (!empty($filter_target_id)) {
    $whereClause .= " AND t.id = ?";
    $params[] = $filter_target_id;
}

if (!$isAdmin) {
    if (empty($fullname)) {
        $whereClause .= " AND 1=0";
    } else {
        $whereClause .= " AND t.responsible_person LIKE ?";
        $params[] = "%" . $fullname . "%";
    }
}

try {
    $stmt = $pdo2->prepare("
        SELECT 
            t.id as target_id,
            t.revenue_name,
            t.target_amount,
            t.target_per_month,
            t.unit_price,
            t.responsible_person,
            IFNULL(SUM(r.collected_amount), 0) as total_collected,
            (SELECT IFNULL(SUM(statement_amount), 0) FROM revenue_statements WHERE target_id = t.id) as total_statement,
            MAX(r.report_date) as latest_report_date
        FROM revenue_targets t
        LEFT JOIN revenue_results r ON t.id = r.target_id
        $whereClause
        GROUP BY t.id
        ORDER BY t.revenue_name ASC
    ");
    $stmt->execute($params);
    $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get Monthly totals across all targets for the fiscal year
    // Fiscal Year usually starts Oct (Month 10) to Sept (Month 9)
    $stmtMonthly = $pdo2->prepare("
        SELECT 
            r.month,
            SUM(r.collected_amount) as month_collected
        FROM revenue_results r
        JOIN revenue_targets t ON r.target_id = t.id
        $whereClause
        GROUP BY r.month
        ORDER BY r.month ASC
    ");
    $stmtMonthly->execute($params);
    $monthlyTotals = $stmtMonthly->fetchAll(PDO::FETCH_ASSOC);

    // Get Monthly statement totals
    $stmtStatements = $pdo2->prepare("
        SELECT 
            s.month,
            SUM(s.statement_amount) as month_statement
        FROM revenue_statements s
        JOIN revenue_targets t ON s.target_id = t.id
        $whereClause
        GROUP BY s.month
        ORDER BY s.month ASC
    ");
    $stmtStatements->execute($params);
    $statementTotals = $stmtStatements->fetchAll(PDO::FETCH_ASSOC);

    // Merge monthly and statements
    $monthlyDataMap = [];
    foreach ($monthlyTotals as $m) {
        $monthlyDataMap[$m['month']] = [
            'month' => $m['month'],
            'month_collected' => $m['month_collected'],
            'month_statement' => 0
        ];
    }
    foreach ($statementTotals as $s) {
        if (!isset($monthlyDataMap[$s['month']])) {
            $monthlyDataMap[$s['month']] = [
                'month' => $s['month'],
                'month_collected' => 0,
                'month_statement' => 0
            ];
        }
        $monthlyDataMap[$s['month']]['month_statement'] = $s['month_statement'];
    }

    $combinedMonthly = array_values($monthlyDataMap);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'summary' => $summary,
            'monthly' => $combinedMonthly,

            'fiscal_year' => $fiscal_year
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
