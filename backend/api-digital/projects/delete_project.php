<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';

$userData = authGuard();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id)) {
    try {
        $sql = "DELETE FROM it_projects WHERE id = :id";
        $stmt = $pdo2->prepare($sql);
        $stmt->bindParam(':id', $data->id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'ลบข้อมูลสำเร็จ'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'ไม่สามารถลบข้อมูลได้'
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Database Error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'ไม่พบรหัสโครงการ'
    ]);
}
