<?php
ini_set('display_errors', 0);
error_reporting(0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require '../../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = ["success" => false];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    if (!isset($_FILES['pdf_file'])) {
        throw new Exception("No file uploaded.");
    }

    $file = $_FILES['pdf_file'];
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = uniqid() . '_' . basename($file['name']);
    $filePath = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        $category = isset($_POST['category']) ? $_POST['category'] : 'general';
        $displayName = isset($_POST['custom_name']) && trim($_POST['custom_name']) !== ''
            ? trim($_POST['custom_name'])
            : $file['name'];

        $stmt = $pdo2->prepare("INSERT INTO pdf_files (file_name, file_path, category, uploaded_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$displayName, $fileName, $category]);
        $response["success"] = true;
    } else {
        throw new Exception("ไม่สามารถอัปโหลดไฟล์ได้");
    }
} catch (Exception $e) {
    $response["error"] = $e->getMessage();
}

echo json_encode($response);
exit;
