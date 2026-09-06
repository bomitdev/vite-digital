<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config.php';

$fullname = $_GET['fullname'] ?? '';

if (empty($fullname)) {
    echo json_encode(["status" => "success", "data" => []]);
    exit;
}

try {
    $sql = "SELECT DISTINCT c.name 
            FROM qi_committees c
            JOIN qi_committee_members m ON c.id = m.committee_id
            WHERE m.officer_name = :fullname";
            
    $stmt = $pdo2->prepare($sql);
    $stmt->execute([':fullname' => $fullname]);
    $teams = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode(["status" => "success", "data" => $teams]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
