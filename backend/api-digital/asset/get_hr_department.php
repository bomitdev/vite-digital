<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

try {
    // hr_department_sub is in $pdo3
    $sql = "SELECT DISTINCT HR_DEPARTMENT_SUB_NAME 
            FROM hr_department_sub 
            WHERE HR_DEPARTMENT_SUB_NAME IS NOT NULL 
            AND HR_DEPARTMENT_SUB_NAME != ''
            ORDER BY HR_DEPARTMENT_SUB_NAME ASC";

    $stmt = $pdo3->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $data]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
