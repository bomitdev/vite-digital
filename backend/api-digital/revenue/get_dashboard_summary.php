<?php
require_once '../../config.php';
require_once '../../auth_utils.php';

$fiscal_year = $_GET['fiscal_year'] ?? (date('Y') + 543 + (date('m') >= 10 ? 1 : 0)); // Thai Fiscal Year Logic
$filter_target_id = $_GET['target_id'] ?? '';
$only_my_targets = !empty($_GET['only_my_targets']) && ($_GET['only_my_targets'] == '1' || $_GET['only_my_targets'] === 'true');

$userData = authOptional();
$isAdmin = false;
$fullname = '';
$deptName = '';
$subDeptName = '';
$deptId = null;
$colleagues = [];

if ($userData) {
    try {
        $stmtUser = $pdo3->prepare("
            SELECT 
                p.ID,
                CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as FULLNAME,
                p.HR_FNAME,
                p.HR_LNAME,
                p.HR_DEPARTMENT_ID,
                d.HR_DEPARTMENT_NAME,
                p.HR_DEPARTMENT_SUB_ID,
                hds.HR_DEPARTMENT_SUB_NAME
            FROM hr_person p
            LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
            LEFT JOIN hr_department d ON p.HR_DEPARTMENT_ID = d.HR_DEPARTMENT_ID
            LEFT JOIN hr_department_sub hds ON p.HR_DEPARTMENT_SUB_ID = hds.HR_DEPARTMENT_SUB_ID
            WHERE p.ID = ?
        ");
        $stmtUser->execute([$userData['uid']]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $fullname = trim($user['FULLNAME']);
            $deptName = trim($user['HR_DEPARTMENT_NAME'] ?? '');
            $subDeptName = trim($user['HR_DEPARTMENT_SUB_NAME'] ?? '');
            $deptId = $user['HR_DEPARTMENT_ID'] ?? null;
            if (strpos($deptName, 'สุขภาพดิจิทัล') !== false || strpos($subDeptName, 'สุขภาพดิจิทัล') !== false ||
                strpos($deptName, 'บริหาร') !== false || strpos($subDeptName, 'บริหาร') !== false ||
                strpos($deptName, 'ประกัน') !== false || strpos($subDeptName, 'ประกัน') !== false) {
                $isAdmin = true;
            }

            // If not admin, find colleagues in the same department (กลุ่มงาน)
            if (!$isAdmin && !empty($deptId)) {
                $stmtColleagues = $pdo3->prepare("
                    SELECT 
                        CONCAT(p.HR_FNAME, ' ', p.HR_LNAME) as SHORT_NAME,
                        CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as FULL_NAME
                    FROM hr_person p
                    LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
                    WHERE p.HR_DEPARTMENT_ID = ? AND p.HR_STATUS_ID = '01'
                ");
                $stmtColleagues->execute([$deptId]);
                $colleagues = $stmtColleagues->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    } catch (PDOException $e) {
        // Fallback to non-admin
    }
}

// Calculate my_targets_count for this user in this fiscal year
$myTargetsCount = 0;
if (!empty($fullname)) {
    try {
        $shortName = (!empty($user['HR_FNAME']) && !empty($user['HR_LNAME'])) 
            ? trim($user['HR_FNAME'] . ' ' . $user['HR_LNAME']) 
            : $fullname;
        $stmtCount = $pdo2->prepare("
            SELECT COUNT(DISTINCT t.id) 
            FROM revenue_targets t 
            WHERE t.fiscal_year = ? 
            AND (t.responsible_person LIKE ? OR t.responsible_person LIKE ?)
        ");
        $stmtCount->execute([$fiscal_year, "%" . $fullname . "%", "%" . $shortName . "%"]);
        $myTargetsCount = (int)$stmtCount->fetchColumn();
    } catch (PDOException $e) {
        $myTargetsCount = 0;
    }
}

$whereClause = "WHERE t.fiscal_year = ?";
$params = [$fiscal_year];

if (!empty($filter_target_id)) {
    $whereClause .= " AND t.id = ?";
    $params[] = $filter_target_id;
}

if ($only_my_targets) {
    // Strictly filter to the user's assigned targets
    if (empty($fullname)) {
        $whereClause .= " AND 1=0";
    } else {
        $myConds = ["t.responsible_person LIKE ?"];
        $myParams = ["%" . $fullname . "%"];

        if (!empty($user['HR_FNAME']) && !empty($user['HR_LNAME'])) {
            $myConds[] = "t.responsible_person LIKE ?";
            $myParams[] = "%" . trim($user['HR_FNAME'] . ' ' . $user['HR_LNAME']) . "%";
        }

        $whereClause .= " AND (" . implode(" OR ", $myConds) . ")";
        $params = array_merge($params, $myParams);
    }
} else if (!$isAdmin) {
    if (empty($fullname)) {
        $whereClause .= " AND 1=0";
    } else {
        $matchConds = ["t.responsible_person LIKE ?"];
        $matchParams = ["%" . $fullname . "%"];

        if (!empty($user['HR_FNAME']) && !empty($user['HR_LNAME'])) {
            $matchConds[] = "t.responsible_person LIKE ?";
            $matchParams[] = "%" . trim($user['HR_FNAME'] . ' ' . $user['HR_LNAME']) . "%";
        }

        if (!empty($deptName)) {
            $matchConds[] = "t.responsible_unit LIKE ?";
            $matchParams[] = "%" . $deptName . "%";
        }
        if (!empty($subDeptName) && $subDeptName !== $deptName) {
            $matchConds[] = "t.responsible_unit LIKE ?";
            $matchParams[] = "%" . $subDeptName . "%";
        }

        foreach ($colleagues as $c) {
            if (!empty($c['SHORT_NAME'])) {
                $matchConds[] = "t.responsible_person LIKE ?";
                $matchParams[] = "%" . trim($c['SHORT_NAME']) . "%";
            }
        }

        $whereClause .= " AND (" . implode(" OR ", $matchConds) . ")";
        $params = array_merge($params, $matchParams);
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
            t.responsible_unit,
            t.claim_program,
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
            'my_targets_count' => $myTargetsCount,
            'fiscal_year' => $fiscal_year
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
