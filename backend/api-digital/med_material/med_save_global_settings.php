<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once '../../config.php';

if (!isset($pdo2)) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['settings']) || !is_array($data['settings'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data format']);
    exit;
}

try {
    $pdo2->beginTransaction();

    $stmt = $pdo2->prepare("
        INSERT INTO mt_global_settings (key_name, setting_value) 
        VALUES (:key, :val) 
        ON DUPLICATE KEY UPDATE setting_value = :val2
    ");

    foreach ($data['settings'] as $key => $val) {
        $stmt->execute([
            ':key' => $key,
            ':val' => (string)$val,
            ':val2' => (string)$val
        ]);
    }

    $pdo2->commit();
    echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
} catch (PDOException $e) {
    $pdo2->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
