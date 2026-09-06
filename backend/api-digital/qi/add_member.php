<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/../../config.php';

$data = json_decode(file_get_contents("php://input"));

$committee_id = $data->committee_id ?? null;
$officer_name = $data->officer_name ?? null;
$role = $data->role ?? 'กรรมการ';

if (!$committee_id || !$officer_name) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "committee_id and officer_name are required"]);
    exit;
}

try {
    // Check if member already exists
    $check_sql = "SELECT id FROM qi_committee_members WHERE committee_id = :committee_id AND officer_name = :officer_name";
    $stmt_check = $pdo2->prepare($check_sql);
    $stmt_check->execute([
        ':committee_id' => $committee_id,
        ':officer_name' => $officer_name
    ]);
    
    if ($stmt_check->fetch()) {
        echo json_encode(["status" => "error", "message" => "เจ้าหน้าที่ท่านนี้อยู่ในทีมแล้ว"]);
        exit;
    }

    $sql = "INSERT INTO qi_committee_members (committee_id, officer_name, role) VALUES (:committee_id, :officer_name, :role)";
    $stmt = $pdo2->prepare($sql);
    $stmt->execute([
        ':committee_id' => $committee_id,
        ':officer_name' => $officer_name,
        ':role' => $role
    ]);

    echo json_encode(["status" => "success", "message" => "Member added successfully"]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
