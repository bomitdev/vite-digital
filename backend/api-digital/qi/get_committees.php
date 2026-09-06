<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config.php';

try {
    $sql = "SELECT c.*, COUNT(m.id) as member_count 
            FROM qi_committees c 
            LEFT JOIN qi_committee_members m ON c.id = m.committee_id 
            GROUP BY c.id 
            ORDER BY c.sort_order ASC, c.id ASC";
            
    $stmt = $pdo2->query($sql);
    $committees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $committees]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
