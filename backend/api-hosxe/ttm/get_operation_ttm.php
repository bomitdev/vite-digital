<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

try {

    // รับค่าช่วงวันที่จาก Vue.js
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '2024-10-01';
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '2025-03-25';

    // เตรียม SQL Query
    $stmt = $pdo1->prepare("
    SELECT o.health_med_operation_item_id as id,
        oi.health_med_operation_item_name as name,
        Count(DISTINCT s.hn) AS C_hn,
        Count(DISTINCT s.vn) AS C_vn,
        Sum(o.service_price) AS sum_price
    FROM health_med_service s 
    LEFT  JOIN health_med_service_operation o ON o.health_med_service_id = s.health_med_service_id
    LEFT  JOIN health_med_operation_item oi ON oi.health_med_operation_item_id = o.health_med_operation_item_id
    WHERE s.service_date BETWEEN :start_date AND :end_date
    and o.health_med_operation_item_id <> ''
    GROUP BY oi.health_med_operation_item_id 
 ");

    // Binding parameters
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);

    // Execute query
    $stmt->execute();

    // Fetch result
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "start_date" => $start_date,
        "end_date" => $end_date,
        "count" => count($data),
        "data" => $data
    ]);

    // debug ชั่วคราว
    //  error_log(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); 


    // Return data as JSON
    // echo json_encode($data);
} catch (PDOException $e) {
    // Return error message as JSON if connection fails
    echo json_encode(["error" => $e->getMessage()]);
}
