<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

try {
    // สร้างการเชื่อมต่อกับฐานข้อมูล
    $pdo1 = new PDO("mysql:host={$db1['host']};dbname={$db1['name']};charset=utf8", $db1['user'], $db1['pass']);
    $pdo1->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // รับค่า start_date, end_date, clinic จากการร้องขอ (เช่น ผ่าน URL หรือ request body)
    $start_date =  2025-03-25;
    $end_date =  2025-03-25;
    $clinic =  001;

    // คำสั่ง SQL
    $stmt = $pdo1->prepare("
        SELECT vn.vstdate, 
        vn.hn, 
        CONCAT(p.pname, p.fname, ' ', p.lname) AS pt_name, 
        vn.age_y
        FROM vn_stat vn
            LEFT JOIN clinic_visit c1 ON vn.vn = c1.vn 
            LEFT JOIN patient p ON vn.hn = p.hn
        WHERE vn.vstdate BETWEEN :start_date AND :end_date
        AND c1.clinic = :clinic
    ");


    // ผูกค่าพารามิเตอร์
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->bindParam(':clinic', $clinic);

    // เรียกใช้งานคำสั่ง
    $stmt->execute();

    // ดึงข้อมูลทั้งหมด
    // Fetch result
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ส่งข้อมูลเป็น JSON
    echo json_encode($data);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit();
}
