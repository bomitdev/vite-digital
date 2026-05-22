<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . '/../../config.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['employees_claim_id']) || !isset($data['dates']) || !is_array($data['dates'])) {
        throw new Exception('ข้อมูลไม่ครบถ้วน');
    }

    $employee_id = $data['employees_claim_id'];
    $dates = $data['dates'];

    $stmt = $pdo2->prepare("INSERT INTO duties_claim(employees_claim_id, date, rate_override, is_special) VALUES (:employees_claim_id, :date, :rate_override, :is_special)");

    $inserted = 0;
    foreach ($dates as $dateObj) {
        $dateStr = isset($dateObj['date']) ? $dateObj['date'] : $dateObj; // Support both object and old string format
        $rate = (isset($dateObj['rate']) && $dateObj['rate'] !== '') ? (int)$dateObj['rate'] : null;
        $is_special = isset($dateObj['is_special']) ? (int)$dateObj['is_special'] : 0;
        
        if (!$dateStr || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) continue;

        if ($stmt->execute([
            ':employees_claim_id' => $employee_id,
            ':date' => $dateStr,
            ':rate_override' => $rate,
            ':is_special' => $is_special
        ])) {
            $inserted++;
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => "เพิ่มข้อมูลเรียบร้อย $inserted รายการ"
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}
