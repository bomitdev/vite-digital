<?php
header("Access-Control-Allow-Origin: *");  
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

require '../config.php';

try {
    /** @var PDO $pdo1 */
    // Enable emulated prepares to allow reusing named parameters in this complex query
    $pdo1->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

    // รับค่าช่วงวันที่จาก Vue.js
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '2024-10-01';
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '2025-09-30';

    // เตรียม SQL Query
    $stmt = $pdo1->prepare("
        SELECT 
            COUNT(DISTINCT vn.vn) as opd_all,
            COUNT(DISTINCT o.an) as ipd_all,
            SUM(CASE WHEN c1.clinic = '001' THEN 1 ELSE 0 END) as dm,
            SUM(CASE WHEN c2.clinic = '002' THEN 1 ELSE 0 END) as ht,
            SUM(CASE WHEN c3.clinic = '024' THEN 1 ELSE 0 END) as ckd,
            SUM(CASE WHEN c4.clinic = '006' THEN 1 ELSE 0 END) as tb,
            SUM(CASE WHEN c5.clinic = '020' THEN 1 ELSE 0 END) as copd,
            SUM(CASE WHEN c6.clinic = '004' THEN 1 ELSE 0 END) as thyroid,
            SUM(CASE WHEN h.spclty = '27' AND h.vstdate BETWEEN :start_date AND :end_date THEN 1 ELSE 0 END) as health_med,
            SUM(CASE WHEN p.spclty = '16' AND p.vstdate BETWEEN :start_date AND :end_date THEN 1 ELSE 0 END) as physic,
            SUM(CASE WHEN pcu.spclty = '18' AND pcu.vstdate BETWEEN :start_date AND :end_date THEN 1 ELSE 0 END) as pcu,
            SUM(CASE WHEN pd.spclty = '09' AND pd.vstdate BETWEEN :start_date AND :end_date  THEN 1 ELSE 0 END) as pd,
            SUM(CASE WHEN d.vn IS NOT NULL THEN 1 ELSE 0 END) as dent,
            SUM(CASE WHEN sm.vn IS NOT NULL THEN 1 ELSE 0 END) as surveil,
            SUM(CASE WHEN e2.vn IS NOT NULL THEN 1 ELSE 0 END) as er,
			SUM(CASE WHEN ro.vn IS NOT NULL THEN 1 ELSE 0 END) as refer_out,
			SUM(CASE WHEN ri.vn IS NOT NULL THEN 1 ELSE 0 END) as refer_in,
            COUNT(DISTINCT CASE WHEN ov_i.export_code = '5' THEN vn.vn END) as telemedicine,
            SUM(vn.income) as sum_income
        FROM vn_stat vn
        LEFT JOIN ovst o on vn.vn = o.vn
        LEFT JOIN clinic_visit c1 ON vn.vn = c1.vn AND c1.clinic = '001'
        LEFT JOIN clinic_visit c2 ON vn.vn = c2.vn AND c2.clinic = '002'
        LEFT JOIN clinic_visit c3 ON vn.vn = c3.vn AND c3.clinic = '024'
        LEFT JOIN clinic_visit c4 ON vn.vn = c4.vn AND c4.clinic = '006'
        LEFT JOIN clinic_visit c5 ON vn.vn = c5.vn AND c5.clinic = '020'
        LEFT JOIN clinic_visit c6 ON vn.vn = c6.vn AND c6.clinic = '004'
        LEFT JOIN physic_main p1 ON vn.vn = p1.vn
        LEFT JOIN vn_stat h ON vn.vn = h.vn AND h.spclty = '27' AND h.vstdate BETWEEN :start_date AND :end_date
        LEFT JOIN vn_stat p ON vn.vn = p.vn AND p.spclty = '16' AND p.vstdate BETWEEN :start_date AND :end_date
        LEFT JOIN vn_stat pcu ON vn.vn = pcu.vn AND pcu.spclty = '18' AND pcu.vstdate BETWEEN :start_date AND :end_date
        LEFT JOIN vn_stat pd ON vn.vn = pd.vn AND pd.spclty = '09' AND pd.vstdate BETWEEN :start_date AND :end_date
        LEFT JOIN dtmain d ON vn.vn = d.vn
        LEFT JOIN surveil_member sm ON vn.vn = sm.vn
        LEFT JOIN er_regist e2 ON vn.vn = e2.vn
        LEFT JOIN referout ro on vn.vn = ro.vn
		LEFT JOIN referin ri on vn.vn = ri.vn
        LEFT JOIN ovstist ov_i on o.ovstist = ov_i.ovstist
        WHERE vn.vstdate BETWEEN :start_date AND :end_date;
    ");

    // Binding parameters
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);

    // Execute query
    $stmt->execute();
    
    // Fetch result
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get today count for Telemedicine
    $stmt_today = $pdo1->prepare("
        SELECT COUNT(DISTINCT o.vn) AS total 
        FROM ovst o 
        LEFT JOIN ovstist i ON o.ovstist = i.ovstist 
        WHERE o.vstdate = CURDATE() AND i.export_code = '5'
    ");
    $stmt_today->execute();
    $data['telemedicine_today'] = $stmt_today->fetchColumn() ?: 0;

    // Get yesterday count for Telemedicine
    $stmt_yesterday = $pdo1->prepare("
        SELECT COUNT(DISTINCT o.vn) AS total 
        FROM ovst o 
        LEFT JOIN ovstist i ON o.ovstist = i.ovstist 
        WHERE o.vstdate = CURDATE() - INTERVAL 1 DAY AND i.export_code = '5'
    ");
    $stmt_yesterday->execute();
    $data['telemedicine_yesterday'] = $stmt_yesterday->fetchColumn() ?: 0;

    // Return data as JSON
    echo json_encode($data);
} catch (PDOException $e) {
    // Return error message as JSON if connection fails
    echo json_encode(["error" => $e->getMessage()]);
}
?>
