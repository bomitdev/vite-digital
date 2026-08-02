<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require '../../config.php';

try {
    $data = json_decode(file_get_contents("php://input"));
    
    if (empty($data->category_name)) {
        echo json_encode(["status" => "error", "message" => "กรุณาระบุชื่อหมวดหมู่"]);
        exit;
    }

    $category_name = trim($data->category_name);
    // Generate a simple key based on timestamp or transliteration if needed.
    // For simplicity, we'll use a prefix with a random string or timestamp.
    $category_key = 'cat_' . time() . '_' . rand(100, 999);
    
    if (!empty($data->category_key)) {
        $category_key = trim($data->category_key);
    }

    $sql = "INSERT INTO document_categories (category_key, category_name) VALUES (:key, :name)";
    $stmt = $pdo2->prepare($sql);
    $stmt->bindParam(':key', $category_key);
    $stmt->bindParam(':name', $category_name);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "เพิ่มหมวดหมู่สำเร็จ", "category_key" => $category_key]);
    } else {
        echo json_encode(["status" => "error", "message" => "ไม่สามารถเพิ่มหมวดหมู่ได้"]);
    }
} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Duplicate entry
        echo json_encode(["status" => "error", "message" => "หมวดหมู่นี้มีอยู่แล้ว"]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error: " . $e->getMessage()]);
}
?>
