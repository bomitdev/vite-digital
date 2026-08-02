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
    
    if (empty($data->category_key)) {
        echo json_encode(["status" => "error", "message" => "กรุณาระบุรหัสหมวดหมู่"]);
        exit;
    }

    $category_key = trim($data->category_key);

    // ตรวจสอบว่ามีเอกสารในหมวดหมู่นี้หรือไม่
    $checkSql = "SELECT COUNT(*) as cnt FROM pdf_files WHERE category = :key";
    $checkStmt = $pdo2->prepare($checkSql);
    $checkStmt->bindParam(':key', $category_key);
    $checkStmt->execute();
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($result['cnt'] > 0) {
        echo json_encode(["status" => "error", "message" => "ไม่สามารถลบได้ เนื่องจากมีเอกสารในหมวดหมู่นี้"]);
        exit;
    }

    $sql = "DELETE FROM document_categories WHERE category_key = :key";
    $stmt = $pdo2->prepare($sql);
    $stmt->bindParam(':key', $category_key);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "ลบหมวดหมู่สำเร็จ"]);
    } else {
        echo json_encode(["status" => "error", "message" => "ไม่สามารถลบหมวดหมู่ได้"]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error: " . $e->getMessage()]);
}
?>
