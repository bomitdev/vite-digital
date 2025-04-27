<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require '../config.php'; // ตรวจสอบ path ให้ถูก

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["error" => "Invalid request method."]);
    exit();
}

if (!isset($_FILES['pdf_file'])) {
    echo json_encode(["error" => "No file uploaded."]);
    exit();
}

$file = $_FILES['pdf_file'];
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$fileName = uniqid() . '_' . basename($file['name']);
$filePath = $uploadDir . $fileName;

if (move_uploaded_file($file['tmp_name'], $filePath)) {
    try {
        $stmt = $pdo2->prepare("INSERT INTO pdf_files (file_name, file_path, uploaded_at) VALUES (?, ?, NOW())");
        $stmt->execute([$file['name'], $fileName]);
        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }
} else {
    http_response_code(500);
    echo json_encode(["error" => "ไม่สามารถอัปโหลดไฟล์ได้"]);
}
