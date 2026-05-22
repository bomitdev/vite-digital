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
         SELECT DATE_FORMAT(an.dchdate, '%Y-%m') AS month, COUNT(DISTINCT an.an) AS ipd_all
        FROM an_stat an
        WHERE an.dchdate BETWEEN :start_date AND :end_date
        GROUP BY DATE_FORMAT(an.dchdate, '%Y-%m')
        ORDER BY DATE_FORMAT(an.dchdate, '%Y-%m');
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
