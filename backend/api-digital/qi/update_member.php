<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/../../config.php';

$data = json_decode(file_get_contents("php://input"));

$id = $data->id ?? null;
$role = $data->role ?? null;
// Optionally allow updating officer_name, but usually just role is enough.
$officer_name = $data->officer_name ?? null; 

if (!$id || !$role) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "id and role are required"]);
    exit;
}

try {
    if ($officer_name) {
        $sql = "UPDATE qi_committee_members SET role = :role, officer_name = :officer_name WHERE id = :id";
        $stmt = $pdo2->prepare($sql);
        $stmt->execute([
            ':role' => $role,
            ':officer_name' => $officer_name,
            ':id' => $id
        ]);
    } else {
        $sql = "UPDATE qi_committee_members SET role = :role WHERE id = :id";
        $stmt = $pdo2->prepare($sql);
        $stmt->execute([
            ':role' => $role,
            ':id' => $id
        ]);
    }

    echo json_encode(["status" => "success", "message" => "Member updated successfully"]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
