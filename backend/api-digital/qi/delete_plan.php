<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/../../config.php';

try {
    $data = json_decode(file_get_contents("php://input"));
    
    if (empty($data->id)) {
        throw new Exception("ID is required");
    }

    $sql = "DELETE FROM qi_improvement_plans WHERE id = :id";
    $stmt = $pdo2->prepare($sql);
    $stmt->execute([':id' => $data->id]);

    echo json_encode(["status" => "success", "message" => "Plan deleted successfully"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
