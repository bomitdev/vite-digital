<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config.php';

$committee_id = $_GET['committee_id'] ?? null;

if (!$committee_id) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "committee_id is required"]);
    exit;
}

try {
    $sql = "SELECT id, officer_name, role, created_at 
            FROM qi_committee_members 
            WHERE committee_id = :committee_id 
            ORDER BY 
                CASE role 
                    WHEN 'ประธาน' THEN 1 
                    WHEN 'รองประธาน' THEN 2 
                    WHEN 'กรรมการ' THEN 3 
                    WHEN 'เลขานุการ' THEN 4 
                    ELSE 5 
                END, 
                officer_name ASC";
            
    $stmt = $pdo2->prepare($sql);
    $stmt->execute([':committee_id' => $committee_id]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $members]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
