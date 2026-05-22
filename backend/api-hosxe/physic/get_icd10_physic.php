<?php
// Header สำหรับรองรับการเชื่อมต่อจาก Vue.js
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// ดึง config database
require __DIR__ . '/../../config.php';

try {
    // สร้างการเชื่อมต่อฐานข้อมูล
    $pdo1 = new PDO("mysql:host={$db1['host']};dbname={$db1['name']};charset=utf8", $db1['user'], $db1['pass']);
    $pdo1->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // รับค่าจาก Vue.js
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '2024-10-01';
    $end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : '2025-03-25';
    $physic     = '16';

    // (Optional) ตรวจสอบรูปแบบวันที่
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
        throw new Exception("Invalid date format. Use YYYY-MM-DD.");
    }

    // สร้าง SQL Query
    $stmt = $pdo1->prepare("
        SELECT vn.pdx,
            COUNT(DISTINCT vn.hn) AS C_hn,
            COUNT(DISTINCT vn.vn) AS C_vn
        FROM vn_stat vn
        WHERE vn.vstdate BETWEEN :start_date AND :end_date
            AND vn.spclty = :physic
        GROUP BY vn.pdx
        ORDER BY C_vn DESC;
    ");

    // Bind พารามิเตอร์
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
     $stmt->bindParam(':physic', $physic);

    // รันคำสั่ง
    $stmt->execute();

    // ดึงผลลัพธ์
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ส่งกลับเป็น JSON
    echo json_encode($data);

} catch (PDOException $e) {
    // แสดง error และ log
    error_log("PDO Error: " . $e->getMessage());
    echo json_encode(array("error" => "Database error occurred."));
} catch (Exception $e) {
    echo json_encode(array("error" => $e->getMessage()));
}
