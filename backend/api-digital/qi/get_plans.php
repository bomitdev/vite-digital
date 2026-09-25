<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config.php';

try {
    $committee_id = isset($_GET['committee_id']) ? intval($_GET['committee_id']) : 0;
    
    if ($committee_id <= 0) {
        throw new Exception("Invalid committee ID");
    }

    $sql = "SELECT * FROM qi_improvement_plans WHERE committee_id = :committee_id ORDER BY id ASC";
    $stmt = $pdo2->prepare($sql);
    $stmt->execute([':committee_id' => $committee_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $data]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
