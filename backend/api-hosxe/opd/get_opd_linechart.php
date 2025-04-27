<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

try {
    // รับค่าช่วงวันที่จาก Vue.js
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '2024-10-01';
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '2025-09-30';

    // เตรียม SQL Query
    $stmt = $pdo1->prepare("
        SELECT DATE_FORMAT(vn.vstdate, '%Y-%m') AS month,
            SUM(CASE WHEN h.spclty = '27' THEN 1 ELSE 0 END) as health_med,
            SUM(CASE WHEN p.spclty = '16' THEN 1 ELSE 0 END) as physic,
            SUM(CASE WHEN pcu.spclty = '18' THEN 1 ELSE 0 END) as pcu,
            SUM(CASE WHEN pd.spclty = '09' THEN 1 ELSE 0 END) as pd,
            SUM(CASE WHEN d.vn IS NOT NULL THEN 1 ELSE 0 END) as dent,
            SUM(CASE WHEN e2.vn IS NOT NULL THEN 1 ELSE 0 END) as er
        FROM vn_stat vn
        LEFT JOIN physic_main p1 ON vn.vn = p1.vn
        LEFT JOIN vn_stat h ON vn.vn = h.vn AND h.spclty = '27'
        LEFT JOIN vn_stat p ON vn.vn = p.vn AND p.spclty = '16'
        LEFT JOIN vn_stat pcu ON vn.vn = pcu.vn AND pcu.spclty = '18'
        LEFT JOIN vn_stat pd ON vn.vn = pd.vn AND pd.spclty = '09'
        LEFT JOIN dtmain d ON vn.vn = d.vn
        LEFT JOIN er_regist e2 ON vn.vn = e2.vn
        WHERE vn.vstdate BETWEEN :start_date AND :end_date
        GROUP BY DATE_FORMAT(vn.vstdate, '%Y-%m')
        ORDER BY DATE_FORMAT(vn.vstdate, '%Y-%m')
    ");
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);

    // Execute query
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ✅ ส่งข้อมูลกลับในรูปแบบ JSON
    echo json_encode($data);
    
} catch (PDOException $e) {
    echo json_encode(array("error" => "Connection failed: " . $e->getMessage()));
    exit();
}
