<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config.php';

try {
    $hn = isset($_GET['hn']) ? trim($_GET['hn']) : '';

    if (empty($hn)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'กรุณาระบุ HN'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!isset($pdo1)) {
        echo json_encode([
            'status' => 'not_found',
            'message' => 'HOSxP Database connection unavailable'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $pdo1->prepare("
        SELECT 
            p.hn,
            CONCAT(COALESCE(p.pname, ''), COALESCE(p.fname, ''), ' ', COALESCE(p.lname, '')) as fullname,
            p.sex,
            TIMESTAMPDIFF(YEAR, p.birthday, CURDATE()) as age,
            p.birthday
        FROM patient p
        WHERE p.hn = :hn
        LIMIT 1
    ");
    $stmt->execute([':hn' => $hn]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($patient) {
        echo json_encode([
            'status' => 'success',
            'data' => $patient
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'status' => 'not_found',
            'message' => 'ไม่พบข้อมูลผู้ป่วยสำหรับ HN นี้'
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
