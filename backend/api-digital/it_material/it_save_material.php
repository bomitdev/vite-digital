<?php
require __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';
require_once '../../cors.php';
require_once __DIR__ . '/it_log_helper.php';

// Secure Auth
$userData = authGuard();

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

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
$balance = isset($data['balance']) && $id == 0 ? intval($data['balance']) : 0;
$imagePath = isset($data['image_path']) ? $data['image_path'] : null;

// Image Upload Handling
if (isset($_FILES['image'])) {
    if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $wsRoot = realpath(__DIR__ . '/../../'); // backend root
            $uploadDir = $wsRoot . '/uploads/materials/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $newFilename = uniqid('mt_it_') . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newFilename)) {
                $imagePath = 'backend/uploads/materials/' . $newFilename;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการบันทึกไฟล์รูปภาพ']);
                exit;
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => "ไฟล์รูปภาพไม่รองรับนามสกุล .$ext (รองรับเฉพาะ jpg, png, gif, webp, jfif)"]);
            exit;
        }
    } else if ($_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        echo json_encode(['status' => 'error', 'message' => 'การอัปโหลดรูปภาพล้มเหลว (Error Code: ' . $_FILES['image']['error'] . ')']);
        exit;
    }
}

if (empty($code) || empty($name) || empty($type) || empty($unit)) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน (รหัส, ชื่อ, ประเภท, หน่วยนับ)']);
    exit;
}

try {
    // Check duplicate code
    $checkStmt = $pdo2->prepare("SELECT id FROM mt_it_materials WHERE code = :code AND id != :id");
    $checkStmt->execute([':code' => $code, ':id' => $id]);
    if ($checkStmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'รหัสสินค้านี้มีอยู่ในระบบแล้ว (Duplicate Code)']);
        exit;
    }

    if ($id > 0) {
        // Update
        $stmt = $pdo2->prepare("
            UPDATE mt_it_materials 
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

        $username = $userData['name'] ?? $userData['user'] ?? 'Unknown User';
        insertItLog($pdo2, $username, 'UPDATE_MATERIAL', "แก้ไขข้อมูลวัสดุคอม รหัส $code (ID: $id)");

        $message = 'อัปเดตข้อมูลวัสดุคอมสำเร็จ';
    } else {
        // Insert
        $stmt = $pdo2->prepare("
            INSERT INTO mt_it_materials (code, name, type, unit, price_per_unit, min_alert, balance, image_path, is_active)
            VALUES (:code, :name, :type, :unit, :price_per_unit, :min_alert, :balance, :image_path, 1)
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

        if ($balance > 0) {
            $stmtLot = $pdo2->prepare("
                INSERT INTO mt_it_lots (material_id, lot_number, vendor, price_per_unit, original_qty, remaining_qty, receive_date)
                VALUES (:material_id, 'LOT-INIT', 'ยอดยกมาตั้งต้น', :price, :qty1, :qty2, NOW())
            ");
            $stmtLot->execute([
                ':material_id' => $id,
                ':price' => $price_per_unit,
                ':qty1' => $balance,
                ':qty2' => $balance
            ]);

            $stmtTx = $pdo2->prepare("
                INSERT INTO mt_it_transactions (material_id, action_type, quantity, total_price, action_date, user_profile_name, receiver_name, reference_dest, note)
                VALUES (:mid, 'IN', :qty, :total_price, NOW(), :user, 'ยอดยกมา', 'ยอดยกมาตั้งต้น', 'ยอดยกมาตั้งต้นจากการเพิ่มวัสดุใหม่')
            ");
            $stmtTx->execute([
                ':mid' => $id,
                ':qty' => $balance,
                ':total_price' => $balance * $price_per_unit,
                ':user' => $userData['name'] ?? $userData['user'] ?? 'System'
            ]);
        }

        $username = $userData['name'] ?? $userData['user'] ?? 'Unknown User';
        insertItLog($pdo2, $username, 'CREATE_MATERIAL', "เพิ่มวัสดุคอมใหม่ รหัส $code (ID: $id)");

        $message = 'เพิ่มวัสดุคอมใหม่สำเร็จ';
    }

    // Auto-sync category to mt_it_categories if not exists
    if (!empty($type)) {
        $catCheck = $pdo2->prepare("SELECT id FROM mt_it_categories WHERE name = ?");
        $catCheck->execute([$type]);
        if (!$catCheck->fetch()) {
            $prefix = '';
            if (preg_match('/^([A-Za-z]+)-/i', $code, $m)) {
                $prefix = strtoupper($m[1]);
            } else if (preg_match('/[A-Za-z]{2,}/', $type, $m)) {
                $prefix = strtoupper(substr($m[0], 0, 3));
            } else {
                $prefix = 'IT';
            }
            $catIns = $pdo2->prepare("INSERT IGNORE INTO mt_it_categories (name, prefix) VALUES (?, ?)");
            $catIns->execute([$type, $prefix]);
        }
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
