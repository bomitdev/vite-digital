<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once __DIR__ . '/../config.php';

/** @var PDO $pdo3 */

try {
    $sql = "SELECT HR_DEPARTMENT_SUB_ID, HR_DEPARTMENT_SUB_NAME 
            FROM hr_department_sub 
            ORDER BY HR_DEPARTMENT_SUB_NAME ASC";

    $stmt = $pdo3->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $data]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
