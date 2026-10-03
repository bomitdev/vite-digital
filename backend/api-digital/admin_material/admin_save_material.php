<?php
require __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';
require_once '../../cors.php';

// Secure Auth
$userData = authGuard();

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

// Support both JSON (old way) and FormData (new way with files)
$data = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        $data = json_decode(file_get_contents("php://input"), true) ?: [];
    } else {
        $data = $_POST;
    }
}

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input data']);
    exit;
}

$id = isset($data['id']) && $data['id'] !== 'null' ? intval($data['id']) : 0;
$code = isset($data['code']) ? trim($data['code']) : '';
$name = isset($data['name']) ? trim($data['name']) : '';
$type = isset($data['type']) ? trim($data['type']) : '';
$unit = isset($data['unit']) ? trim($data['unit']) : '';
$price_per_unit = isset($data['price_per_unit']) ? floatval($data['price_per_unit']) : 0.00;
$min_alert = isset($data['min_alert']) ? intval($data['min_alert']) : 5;
// Balance ไม่รับค่าจากการสร้าง เพราะให้รับเข้าจาก Transaction เท่านั้น
$balance = isset($data['balance']) && $id == 0 ? intval($data['balance']) : 0;
$imagePath = isset($data['image_path']) ? $data['image_path'] : null;

// Image Upload Handling
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $filename = $_FILES['image']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed)) {
        $wsRoot = realpath(__DIR__ . '/../../'); // backend root
        $uploadDir = $wsRoot . '/uploads/materials/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $newFilename = uniqid('mt_') . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newFilename)) {
            $imagePath = 'backend/uploads/materials/' . $newFilename;
        }
    }
}

if (empty($code) || empty($name) || empty($type) || empty($unit)) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน (รหัส, ชื่อ, ประเภท, หน่วยนับ)']);
    exit;
}

try {
    // Check duplicate code
    $checkStmt = $pdo2->prepare("SELECT id FROM mt_admin_materials WHERE code = :code AND id != :id");
    $checkStmt->execute([':code' => $code, ':id' => $id]);
    if ($checkStmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'รหัสสินค้านี้มีอยู่ในระบบแล้ว (Duplicate Code)']);
        exit;
    }

    if ($id > 0) {
        // Update
        $stmt = $pdo2->prepare("
            UPDATE mt_admin_materials 
            SET code = :code, name = :name, type = :type, unit = :unit, price_per_unit = :price_per_unit, min_alert = :min_alert, image_path = :image_path
            WHERE id = :id
        ");
        $stmt->execute([
            ':code' => $code,
            ':name' => $name,
            ':type' => $type,
            ':unit' => $unit,
            ':price_per_unit' => $price_per_unit,
            ':min_alert' => $min_alert,
            ':image_path' => $imagePath,
            ':id' => $id
        ]);

        $message = 'อัปเดตข้อมูลวัสดุสำเร็จ';
    } else {
        // Insert
        // กรณีตั้งต้นสินค้าใหม่ อาจจะมี balance มาด้วย (ถ้ายกยอด) 
        // ปกติให้เป็น 0 แล้วไปกดรับเข้าทีหลัง แต่ถ้ายอมให้ใส่ครั้งแรกก็ทำได้
        $stmt = $pdo2->prepare("
            INSERT INTO mt_admin_materials (code, name, type, unit, price_per_unit, min_alert, balance, image_path)
            VALUES (:code, :name, :type, :unit, :price_per_unit, :min_alert, :balance, :image_path)
        ");
        $stmt->execute([
            ':code' => $code,
            ':name' => $name,
            ':type' => $type,
            ':unit' => $unit,
            ':price_per_unit' => $price_per_unit,
            ':min_alert' => $min_alert,
            ':balance' => $balance,
            ':image_path' => $imagePath
        ]);
        $id = $pdo2->lastInsertId();
        $message = 'เพิ่มวัสดุใหม่สำเร็จ';
    }

    echo json_encode([
        'status' => 'success',
        'message' => $message,
        'id' => $id
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'DB Error: ' . $e->getMessage()
    ]);
}
