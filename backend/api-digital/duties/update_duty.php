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

    $id = $data['id'];         // id ของ duties
    $newDate = $data['date'];  // วันที่ใหม่

    $stmt = $pdo2->prepare("
        UPDATE duties_it
        SET date = :date
        WHERE id = :id
    ");
    $stmt->execute([
        ':date' => $newDate,
        ':id' => $id
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'แก้ไขวันที่สำเร็จ'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error updating duty: ' . $e->getMessage()
    ]);
}
