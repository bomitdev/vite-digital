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

    // รับค่าช่วงวันที่จาก Vue.js
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '2024-10-01';
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '2025-03-25';

    // เตรียม SQL Query
    $stmt = $pdo1->prepare("
        SELECT SUM(CASE WHEN er_emergency_type ='1' THEN 1 ELSE 0 END) AS 'Resuscitate',
                SUM(CASE WHEN er_emergency_type ='2' THEN 1 ELSE 0 END) AS 'Emergency',
                SUM(CASE WHEN er_emergency_type ='3' THEN 1 ELSE 0 END) AS 'Urgency',
                SUM(CASE WHEN er_emergency_type ='4' THEN 1 ELSE 0 END) AS 'Semi_Urgency',
                SUM(CASE WHEN er_emergency_type ='5' THEN 1 ELSE 0 END) AS 'Non_Urgency'
        FROM er_regist
        WHERE DATE(enter_er_time)  BETWEEN :start_date AND :end_date;
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
