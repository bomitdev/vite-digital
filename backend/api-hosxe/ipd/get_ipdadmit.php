<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

try {

    // เตรียม SQL Query
    $stmt = $pdo1->prepare("
    SELECT 
        count(i.an) as ipd_all_now,
        sum(case when i.ward = '06' THEN 1 ELSE 0 END) as ipd,
        sum(case when i.ward = '02' THEN 1 ELSE 0 END) as ipd_labor,
        sum(case when i.ward = '07' THEN 1 ELSE 0 END) as ipd_homeward,
        sum(case when p.hn IS NOT NULL THEN 1 ELSE 0 END) as hr_hos
    FROM ipt i 
        LEFT JOIN patient_hospital_officer p ON i.hn = p.hn
    WHERE i.dchstts IS NULL
    ");

    // Execute query
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    // ✅ ส่งข้อมูลกลับในรูปแบบ JSON
    echo json_encode($data);
} catch (PDOException $e) {
    echo json_encode(array("error" => "Connection failed: " . $e->getMessage()));
    exit();
}
