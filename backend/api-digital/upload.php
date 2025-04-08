<?php
// เพิ่มการตั้งค่า CORS เพื่ออนุญาตการเข้าถึงจากทุกโดเมน
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// ถ้าเป็น preflight request (OPTIONS) ให้ส่งการตอบกลับทันที
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Max-Age: 86400"); // อนุญาตให้ cache คำขอ preflight ได้นาน 1 วัน
    exit(0);
}


$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$maxFileSize = 20 * 1024 * 1024; // จำกัดขนาดไฟล์ไม่เกิน 10 MB

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES['file']['name'])) {
        $fileName = basename($_FILES['file']['name']);
        
        // ตรวจสอบขนาดไฟล์
        if ($_FILES['file']['size'] > $maxFileSize) {
            http_response_code(400);
            echo json_encode(['message' => 'ไฟล์มีขนาดเกินกว่าที่อนุญาต (สูงสุด 10 MB)']);
            exit;
        }

        // แปลงชื่อไฟล์ให้ปลอดภัย
        $fileName = preg_replace("/[^a-zA-Z0-9\-_\.]/", "_", $fileName); // แทนที่อักขระที่ไม่ปลอดภัย
        $targetFilePath = $uploadDir . $fileName;

        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

        // ตรวจสอบว่าเป็นไฟล์ PDF หรือไม่
        if ($fileType === 'pdf') {
            if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFilePath)) {
                echo json_encode(['message' => 'อัปโหลดไฟล์สำเร็จ']);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['message' => 'อัปโหลดได้เฉพาะไฟล์ PDF']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['message' => 'ไม่มีไฟล์ถูกเลือก']);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'ไม่รองรับวิธีการนี้']);
}
?>
