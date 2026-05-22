<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . '/../../config.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    $id = $data['id'];

    $stmt = $pdo2->prepare("DELETE FROM duties_it WHERE id = :id");
    $stmt->execute([
        ':id' => $id
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'ลบข้อมูลสำเร็จ'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error deleting duty: ' . $e->getMessage()
    ]);
}
