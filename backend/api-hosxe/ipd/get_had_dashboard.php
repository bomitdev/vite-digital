<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config.php';

try {
    if (!isset($pdo1)) {
        throw new Exception("HOSxP Database connection missing.");
    }

    // Default to current fiscal year
    $startDate = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : '2025-10-01';
    $endDate = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : '2026-09-30';
    $statusFilter = isset($_GET['status']) ? trim($_GET['status']) : 'all'; // all, assessed, unassessed
    $selectedDrug = isset($_GET['drug_name']) ? trim($_GET['drug_name']) : '';
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $viewMode = isset($_GET['view_mode']) ? trim($_GET['view_mode']) : 'detail'; // 'detail' (per drug row) or 'admission' (grouped by AN)

    // 1. Fetch Summary Metrics & Stats (computed for the whole date range)
    $sqlMetrics = "
        SELECT 
            COUNT(*) AS total_records,
            COUNT(DISTINCT drug_list.an) AS total_admissions,
            COUNT(DISTINCT drug_list.hn) AS total_patients,
            COUNT(DISTINCT CASE WHEN assess.an IS NOT NULL THEN drug_list.an END) AS assessed_admissions,
            COUNT(DISTINCT CASE WHEN assess.an IS NULL THEN drug_list.an END) AS unassessed_admissions,
            COUNT(DISTINCT drug_list.drug_name) AS distinct_drugs_count
        FROM (
            SELECT
                a.an,
                a.hn,
                a.regdate,
                a.dchdate,
                d.name AS drug_name
            FROM an_stat a
            JOIN opitemrece o ON o.an = a.an
            JOIN drugitems d ON d.icode = o.icode
            WHERE (
                (a.dchdate BETWEEN :start_date AND :end_date)
                OR (a.dchdate IS NULL AND o.vstdate BETWEEN :start_date2 AND :end_date2)
            )
              AND d.show_notify_text LIKE '%HIGH ALERT DRUG%'
              AND d.istatus = 'Y'
            GROUP BY a.an, a.hn, a.regdate, a.dchdate, d.name
        ) AS drug_list
        LEFT JOIN (
            SELECT DISTINCT h.an
            FROM assessment_head h
            JOIN assessment_form f ON f.assessment_form_id = h.assessment_form_id
            WHERE f.assessment_form_name LIKE '[HAD]%'
              AND h.an IS NOT NULL AND h.an <> ''
        ) AS assess ON drug_list.an = assess.an
    ";

    $stmtMetrics = $pdo1->prepare($sqlMetrics);
    $stmtMetrics->execute([
        ':start_date' => $startDate,
        ':end_date' => $endDate,
        ':start_date2' => $startDate,
        ':end_date2' => $endDate
    ]);
    $metrics = $stmtMetrics->fetch(PDO::FETCH_ASSOC);

    $totalAdm = (int)($metrics['total_admissions'] ?? 0);
    $assessedAdm = (int)($metrics['assessed_admissions'] ?? 0);
    $unassessedAdm = (int)($metrics['unassessed_admissions'] ?? 0);
    $assessRate = $totalAdm > 0 ? round(($assessedAdm / $totalAdm) * 100, 1) : 0;

    $metrics['total_records'] = (int)($metrics['total_records'] ?? 0);
    $metrics['total_admissions'] = $totalAdm;
    $metrics['total_patients'] = (int)($metrics['total_patients'] ?? 0);
    $metrics['assessed_admissions'] = $assessedAdm;
    $metrics['unassessed_admissions'] = $unassessedAdm;
    $metrics['assessment_rate'] = $assessRate;
    $metrics['distinct_drugs_count'] = (int)($metrics['distinct_drugs_count'] ?? 0);

    // 2. Monthly Trend (group by dchdate YYYY-MM)
    $sqlMonthly = "
        SELECT 
            DATE_FORMAT(COALESCE(drug_list.dchdate, drug_list.vstdate), '%Y-%m') AS month_key,
            COUNT(DISTINCT drug_list.an) AS total_admissions,
            COUNT(DISTINCT CASE WHEN assess.an IS NOT NULL THEN drug_list.an END) AS assessed_admissions,
            COUNT(DISTINCT CASE WHEN assess.an IS NULL THEN drug_list.an END) AS unassessed_admissions
        FROM (
            SELECT a.an, a.dchdate, o.vstdate
            FROM an_stat a
            JOIN opitemrece o ON o.an = a.an
            JOIN drugitems d ON d.icode = o.icode
            WHERE (
                (a.dchdate BETWEEN :start_date AND :end_date)
                OR (a.dchdate IS NULL AND o.vstdate BETWEEN :start_date2 AND :end_date2)
            )
              AND d.show_notify_text LIKE '%HIGH ALERT DRUG%'
              AND d.istatus = 'Y'
            GROUP BY a.an, a.dchdate, o.vstdate
        ) AS drug_list
        LEFT JOIN (
            SELECT DISTINCT h.an
            FROM assessment_head h
            JOIN assessment_form f ON f.assessment_form_id = h.assessment_form_id
            WHERE f.assessment_form_name LIKE '[HAD]%'
              AND h.an IS NOT NULL AND h.an <> ''
        ) AS assess ON drug_list.an = assess.an
        GROUP BY DATE_FORMAT(COALESCE(drug_list.dchdate, drug_list.vstdate), '%Y-%m')
        ORDER BY month_key ASC
    ";
    $stmtMonthly = $pdo1->prepare($sqlMonthly);
    $stmtMonthly->execute([
        ':start_date' => $startDate,
        ':end_date' => $endDate,
        ':start_date2' => $startDate,
        ':end_date2' => $endDate
    ]);
    $monthlyRows = $stmtMonthly->fetchAll(PDO::FETCH_ASSOC);

    // Thai month format
    $thaiMonths = [
        '01' => 'ม.ค.', '02' => 'ก.พ.', '03' => 'มี.ค.', '04' => 'เม.ย.',
        '05' => 'พ.ค.', '06' => 'มิ.ย.', '07' => 'ก.ค.', '08' => 'ส.ค.',
        '09' => 'ก.ย.', '10' => 'ต.ค.', '11' => 'พ.ย.', '12' => 'ธ.ค.'
    ];

    $monthlyTrends = [];
    foreach ($monthlyRows as $row) {
        $parts = explode('-', $row['month_key']);
        $label = $row['month_key'];
        if (count($parts) === 2) {
            $m = $parts[1];
            $y = (int)$parts[0] + 543;
            $label = ($thaiMonths[$m] ?? $m) . ' ' . substr((string)$y, 2);
        }
        $monthlyTrends[] = [
            'month_key' => $row['month_key'],
            'label' => $label,
            'total_admissions' => (int)$row['total_admissions'],
            'assessed_admissions' => (int)$row['assessed_admissions'],
            'unassessed_admissions' => (int)$row['unassessed_admissions']
        ];
    }

    // 3. Top High Alert Drugs
    $sqlTopDrugs = "
        SELECT 
            drug_list.drug_name,
            COUNT(DISTINCT drug_list.an) AS admission_count,
            COUNT(DISTINCT CASE WHEN assess.an IS NOT NULL THEN drug_list.an END) AS assessed_count,
            COUNT(DISTINCT CASE WHEN assess.an IS NULL THEN drug_list.an END) AS unassessed_count
        FROM (
            SELECT a.an, d.name AS drug_name
            FROM an_stat a
            JOIN opitemrece o ON o.an = a.an
            JOIN drugitems d ON d.icode = o.icode
            WHERE (
                (a.dchdate BETWEEN :start_date AND :end_date)
                OR (a.dchdate IS NULL AND o.vstdate BETWEEN :start_date2 AND :end_date2)
            )
              AND d.show_notify_text LIKE '%HIGH ALERT DRUG%'
              AND d.istatus = 'Y'
            GROUP BY a.an, d.name
        ) AS drug_list
        LEFT JOIN (
            SELECT DISTINCT h.an
            FROM assessment_head h
            JOIN assessment_form f ON f.assessment_form_id = h.assessment_form_id
            WHERE f.assessment_form_name LIKE '[HAD]%'
              AND h.an IS NOT NULL AND h.an <> ''
        ) AS assess ON drug_list.an = assess.an
        GROUP BY drug_list.drug_name
        ORDER BY admission_count DESC
    ";
    $stmtTopDrugs = $pdo1->prepare($sqlTopDrugs);
    $stmtTopDrugs->execute([
        ':start_date' => $startDate,
        ':end_date' => $endDate,
        ':start_date2' => $startDate,
        ':end_date2' => $endDate
    ]);
    $topDrugs = $stmtTopDrugs->fetchAll(PDO::FETCH_ASSOC);

    // Collect drug options for dropdown
    $drugOptions = array_map(function($d) {
        return [
            'name' => $d['drug_name'],
            'count' => (int)$d['admission_count']
        ];
    }, $topDrugs);

    // 4. Detail Table Records (with filters applied)
    $filterParams = [
        ':start_date' => $startDate,
        ':end_date' => $endDate,
        ':start_date2' => $startDate,
        ':end_date2' => $endDate
    ];

    $drugWhere = "";
    if (!empty($selectedDrug)) {
        $drugWhere = " AND d.name = :selected_drug ";
        $filterParams[':selected_drug'] = $selectedDrug;
    }

    if ($viewMode === 'admission') {
        // Grouped by admission (AN)
        $sqlRecords = "
            SELECT 
                drug_list.an,
                drug_list.hn,
                drug_list.pt_name,
                drug_list.regdate,
                drug_list.dchdate,
                drug_list.ward_name,
                drug_list.drug_names AS drug_name,
                drug_list.drug_count,
                assess.assess_date,
                assess.assessment_forms AS assessment_form_name,
                assess.assess_staffs AS assessment_head_staff,
                CASE WHEN assess.assess_count > 0 THEN 1 ELSE 0 END AS is_assessed
            FROM (
                SELECT
                    a.an,
                    a.hn,
                    CONCAT(p.pname, p.fname, ' ', p.lname) AS pt_name,
                    a.regdate,
                    a.dchdate,
                    COALESCE(w.name, 'ไม่ระบุ') AS ward_name,
                    GROUP_CONCAT(DISTINCT d.name ORDER BY d.name SEPARATOR ', ') AS drug_names,
                    COUNT(DISTINCT d.icode) AS drug_count
                FROM an_stat a
                JOIN opitemrece o ON o.an = a.an
                JOIN drugitems d ON d.icode = o.icode
                JOIN patient p ON a.hn = p.hn
                LEFT JOIN ward w ON w.ward = a.ward
                WHERE (
                    (a.dchdate BETWEEN :start_date AND :end_date)
                    OR (a.dchdate IS NULL AND o.vstdate BETWEEN :start_date2 AND :end_date2)
                )
                  AND d.show_notify_text LIKE '%HIGH ALERT DRUG%' 
                  AND d.istatus = 'Y'
                  $drugWhere
                GROUP BY a.an, a.hn, p.pname, p.fname, p.lname, a.regdate, a.dchdate, w.name
            ) AS drug_list
            LEFT JOIN (
                SELECT 
                    h.an, 
                    MAX(DATE(h.assessment_head_datetime)) AS assess_date,
                    GROUP_CONCAT(DISTINCT f.assessment_form_name ORDER BY f.assessment_form_name SEPARATOR ' | ') AS assessment_forms,
                    GROUP_CONCAT(DISTINCT h.assessment_head_staff ORDER BY h.assessment_head_staff SEPARATOR ', ') AS assess_staffs,
                    COUNT(DISTINCT h.assessment_head_id) AS assess_count
                FROM assessment_head h
                JOIN assessment_form f ON f.assessment_form_id = h.assessment_form_id
                WHERE f.assessment_form_name LIKE '[HAD]%'
                  AND h.an IS NOT NULL AND h.an <> ''
                GROUP BY h.an
            ) AS assess ON drug_list.an = assess.an
            WHERE 1=1
        ";
    } else {
        // Detailed mode (per drug record as requested in the user's query)
        $sqlRecords = "
            SELECT 
                drug_list.an,
                drug_list.hn,
                drug_list.pt_name,
                drug_list.regdate,
                drug_list.dchdate,
                drug_list.ward_name,
                drug_list.drug_name,
                1 AS drug_count,
                assess.assess_date,
                assess.assessment_form_name,
                assess.assessment_head_staff,
                CASE WHEN assess.an IS NOT NULL THEN 1 ELSE 0 END AS is_assessed
            FROM (
                SELECT
                    a.an,
                    a.hn,
                    CONCAT(p.pname, p.fname, ' ', p.lname) AS pt_name,
                    a.regdate,
                    a.dchdate,
                    COALESCE(w.name, 'ไม่ระบุ') AS ward_name,
                    d.name AS drug_name
                FROM an_stat a
                JOIN opitemrece o ON o.an = a.an
                JOIN drugitems d ON d.icode = o.icode
                JOIN patient p ON a.hn = p.hn
                LEFT JOIN ward w ON w.ward = a.ward
                WHERE (
                    (a.dchdate BETWEEN :start_date AND :end_date)
                    OR (a.dchdate IS NULL AND o.vstdate BETWEEN :start_date2 AND :end_date2)
                )
                  AND d.show_notify_text LIKE '%HIGH ALERT DRUG%' 
                  AND d.istatus = 'Y'
                  $drugWhere
                GROUP BY a.an, a.hn, p.pname, p.fname, p.lname, a.regdate, a.dchdate, w.name, d.name
            ) AS drug_list
            LEFT JOIN (
                SELECT 
                    h.an, 
                    MAX(DATE(h.assessment_head_datetime)) AS assess_date,
                    GROUP_CONCAT(DISTINCT f.assessment_form_name ORDER BY f.assessment_form_name SEPARATOR ' | ') AS assessment_form_name,
                    GROUP_CONCAT(DISTINCT h.assessment_head_staff ORDER BY h.assessment_head_staff SEPARATOR ', ') AS assessment_head_staff
                FROM assessment_head h
                JOIN assessment_form f ON f.assessment_form_id = h.assessment_form_id
                WHERE f.assessment_form_name LIKE '[HAD]%'
                  AND h.an IS NOT NULL AND h.an <> ''
                GROUP BY h.an
            ) AS assess ON drug_list.an = assess.an
            WHERE 1=1
        ";
    }

    if ($statusFilter === 'assessed') {
        $sqlRecords .= " AND (assess.assess_date IS NOT NULL OR assess.assessment_form_name IS NOT NULL) ";
    } else if ($statusFilter === 'unassessed') {
        $sqlRecords .= " AND assess.assess_date IS NULL AND assess.assessment_form_name IS NULL ";
    }

    if (!empty($search)) {
        $sqlRecords .= " AND (drug_list.an LIKE :search OR drug_list.hn LIKE :search OR drug_list.pt_name LIKE :search OR drug_list.drug_name LIKE :search) ";
        $filterParams[':search'] = '%' . $search . '%';
    }

    $sqlRecords .= " ORDER BY drug_list.regdate DESC ";

    $stmtRecords = $pdo1->prepare($sqlRecords);
    $stmtRecords->execute($filterParams);
    $records = $stmtRecords->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'filter' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $statusFilter,
                'drug_name' => $selectedDrug,
                'search' => $search,
                'view_mode' => $viewMode
            ],
            'metrics' => $metrics,
            'monthly_trends' => $monthlyTrends,
            'top_drugs' => $topDrugs,
            'drug_options' => $drugOptions,
            'records' => $records
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
