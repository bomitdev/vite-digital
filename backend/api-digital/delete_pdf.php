<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id']) || !isset($data['file_path'])) {
    echo json_encode(["error" => "ข้อมูลไม่ครบถ้วน"]);
    exit();
}

$id = $data['id'];
$filePath = __DIR__ . '/uploads/' . $data['file_path'];

try {
    // ลบไฟล์จริง
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // ลบจากฐานข้อมูล
    $stmt = $pdo2->prepare("DELETE FROM pdf_files WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(["success" => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
