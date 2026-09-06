<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/../../config.php';

$data = json_decode(file_get_contents("php://input"));
$id = $data->id ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "id is required"]);
    exit;
}

try {
    $sql = "DELETE FROM qi_committee_members WHERE id = :id";
    $stmt = $pdo2->prepare($sql);
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["status" => "success", "message" => "Member removed successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Member not found"]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
