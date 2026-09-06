<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../../config.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id)) {
    try {
        $description = isset($data->description) ? $data->description : null;
        
        $sql = "UPDATE qi_committees SET description = :description WHERE id = :id";
        $stmt = $pdo2->prepare($sql);
        
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':id', $data->id);
        
        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(["status" => "success", "message" => "อัพเดทบทบาทหน้าที่สำเร็จ"]);
        } else {
            http_response_code(503);
            echo json_encode(["status" => "error", "message" => "ไม่สามารถอัพเดทได้"]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "ข้อมูลไม่ครบถ้วน"]);
}
