<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require __DIR__ . '/../../config.php';

try {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->is_locked)) {
        throw new Exception("Missing required fields");
    }

    $is_locked = intval($data->is_locked); // 0 or 1

    $stmt = $pdo2->prepare("UPDATE kpi_definitions SET is_locked = :is_locked");
    $stmt->execute([':is_locked' => $is_locked]);

    echo json_encode(["status" => "success", "message" => "All locks updated successfully"]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
