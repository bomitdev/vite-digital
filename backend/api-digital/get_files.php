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


require '../config.php';

$uploadDir = 'uploads/';
$files = [];

try {
    // สร้างการเชื่อมต่อกับฐานข้อมูล
    $pdo2 = new PDO("mysql:host={$db2['host']};dbname={$db2['name']};charset=utf8", $db2['user'], $db2['pass']);
    $pdo2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ตรวจสอบการเชื่อมต่อฐานข้อมูล
    if (!$pdo2) {
        echo json_encode(["error" => "ไม่สามารถเชื่อมต่อกับฐานข้อมูล"]);
        exit;
    }

    // ตรวจสอบว่าโฟลเดอร์มีไฟล์หรือไม่
    if (is_dir($uploadDir)) {
        $fileList = scandir($uploadDir);
        foreach ($fileList as $file) {
            if ($file !== '.' && $file !== '..') {
                $files[] = [
                    'id' => uniqid(),
                    'filename' => $file,
                    'path' => $uploadDir . $file
                ];
                
                // ถ้าต้องการบันทึกไฟล์ลงฐานข้อมูล
                // เพิ่มไฟล์ลงในฐานข้อมูล
                // $stmt = $pdo2->prepare("INSERT INTO files (filename, path) VALUES (?, ?)");
                // $stmt->execute([$file, $uploadDir . $file]);
            }
        }
    }

    echo json_encode($files);
} catch (PDOException $e) {
    echo json_encode(["error" => "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(["error" => "เกิดข้อผิดพลาด: " . $e->getMessage()]);
}
