<?php
header("Access-Control-Allow-Origin: *");  
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

require '../config.php';

try {
    // สร้างการเชื่อมต่อกับฐานข้อมูล
    $pdo1 = new PDO("mysql:host={$db1['host']};dbname={$db1['name']};charset=utf8", $db1['user'], $db1['pass']);
    $pdo1->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // รับค่าช่วงวันที่จาก Vue.js
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '2024-10-01';
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '2025-03-25';

    // เตรียม SQL Query
    $stmt = $pdo1->prepare("
        SELECT 
            COUNT(DISTINCT vn.vn) as opd_all,
            SUM(CASE WHEN c1.clinic = '001' THEN 1 ELSE 0 END) as dm,
            SUM(CASE WHEN c2.clinic = '002' THEN 1 ELSE 0 END) as ht,
            SUM(CASE WHEN p1.vn IS NOT NULL THEN 1 ELSE 0 END) as physic,
            (SELECT COUNT(*) FROM physic_main_ipd ps WHERE ps.vstdate BETWEEN :start_date AND :end_date) as physic_ipd,
            SUM(CASE WHEN h.spclty = '27' AND h.vstdate BETWEEN :start_date AND :end_date THEN 1 ELSE 0 END) as health_med,
            SUM(CASE WHEN d.vn IS NOT NULL THEN 1 ELSE 0 END) as dent,
            SUM(CASE WHEN sm.vn IS NOT NULL THEN 1 ELSE 0 END) as surveil,
            SUM(CASE WHEN e2.vn IS NOT NULL THEN 1 ELSE 0 END) as er,
            SUM(CASE WHEN pcu.spclty = 18 THEN 1 ELSE 0 END) as pcu
        FROM vn_stat vn
        LEFT JOIN clinic_visit c1 ON vn.vn = c1.vn AND c1.clinic = '001'
        LEFT JOIN clinic_visit c2 ON vn.vn = c2.vn AND c2.clinic = '002'
        LEFT JOIN physic_main p1 ON vn.vn = p1.vn
        LEFT JOIN vn_stat h ON vn.vn = h.vn AND h.spclty = '27' AND h.vstdate BETWEEN :start_date AND :end_date
        LEFT JOIN dtmain d ON vn.vn = d.vn
        LEFT JOIN surveil_member sm ON vn.vn = sm.vn
        LEFT JOIN er_regist e2 ON vn.vn = e2.vn
        LEFT JOIN vn_stat pcu ON vn.vn = pcu.vn AND pcu.spclty = 18
        WHERE vn.vstdate BETWEEN :start_date AND :end_date;
    ");

    // Binding parameters
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);

    // Execute query
    $stmt->execute();
    
    // Fetch result
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    // Return data as JSON
    echo json_encode($data);
} catch (PDOException $e) {
    // Return error message as JSON if connection fails
    echo json_encode(["error" => $e->getMessage()]);
}
?>
