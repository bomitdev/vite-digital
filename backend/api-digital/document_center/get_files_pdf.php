<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require '../../config.php';

try {
    $category = isset($_GET['category']) ? $_GET['category'] : null;
    $sql = "SELECT * FROM pdf_files";
    $params = [];

    if ($category) {
        $sql .= " WHERE category = ?";
        $params[] = $category;
    }

    $sql .= " ORDER BY uploaded_at DESC";

    $stmt = $pdo2->prepare($sql);
    $stmt->execute($params);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // เพิ่มรูปแบบวันที่ให้อ่านง่าย (optional)
    foreach ($files as &$file) {
        $file['uploaded_at_formatted'] = date('d/m/Y H:i', strtotime($file['uploaded_at']));
    }

    echo json_encode($files);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "เกิดข้อผิดพลาด: " . $e->getMessage()]);
}
