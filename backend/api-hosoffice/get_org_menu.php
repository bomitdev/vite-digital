<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../config.php';

/** @var PDO $pdo3 */

try {
    $sql = "SELECT HR_DEPARTMENT_ID as id, HR_DEPARTMENT_NAME as name 
            FROM hr_department 
            WHERE ACTIVE = 'True' 
            ORDER BY HR_DEPARTMENT_NAME ASC";
    $stmt = $pdo3->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $data]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
