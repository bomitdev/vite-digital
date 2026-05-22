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
     select o.icd10tm_operation_code as code, o.description_english as eng, o.description_thai as thai, COUNT(DISTINCT m.hn) as c_hn, COUNT(DISTINCT m.vn) as c_vn 
    FROM dtmain m  
    LEFT OUTER JOIN dtmain_area a on m.dtmain_id=a.dtmain_id  
    LEFT OUTER JOIN icd10tm_operation o ON a.icd10tm_operation_code=o.icd10tm_operation_code 
    WHERE m.vstdate BETWEEN :start_date and :end_date  
    AND a.icd10tm_operation_code is not null 
    GROUP BY a.icd10tm_operation_code 
    ORDER BY c_vn DESC 
    
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