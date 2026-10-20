<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config.php';

try {
    if (!isset($pdo2)) {
        throw new Exception("Database connection missing (pdo2).");
    }

    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    $id = isset($data['id']) ? (int)$data['id'] : 0;

    if ($id <= 0) {
        throw new Exception("Invalid ID parameter.");
    }

    $stmt = $pdo2->prepare("DELETE FROM warfarin_calc_logs WHERE id = :id");
    $stmt->execute([':id' => $id]);

    echo json_encode([
        'status' => 'success',
        'message' => 'ลบรายการประวัติเรียบร้อยแล้ว'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
