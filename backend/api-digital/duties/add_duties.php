<?php
require __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';

// Secure Auth
$userData = authGuard();

header('Content-Type: application/json; charset=utf-8');

try {
    // รับข้อมูลจาก Vue
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['employee_id']) || !isset($data['dates']) || !is_array($data['dates'])) {
        throw new Exception('ข้อมูลไม่ครบถ้วน');
    }

    $employee_id = $data['employee_id'];
    $dates = $data['dates']; // array ของวันที่

    $stmt = $pdo2->prepare("INSERT INTO duties_it(employees_id, date, rate_override, is_special) VALUES (:employee_id, :date, :rate_override, :is_special)");

    foreach ($dates as $item) {
        $date_val = is_array($item) ? $item['date'] : $item;
        $rate_val = (is_array($item) && isset($item['rate']) && $item['rate'] !== '') ? (int)$item['rate'] : null;
        $is_special = (is_array($item) && isset($item['is_special'])) ? (int)$item['is_special'] : 0;
        
        $stmt->execute([
            ':employee_id' => $employee_id,
            ':date' => $date_val,
            ':rate_override' => $rate_val,
            ':is_special' => $is_special
        ]);
    }

    $response = [
        'status' => 'success',
        'message' => 'เพิ่มวันที่ข้อมูลเรียบร้อยแล้ว',
    ];
    echo json_encode($response);
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => 'Error adding duties: ' . $e->getMessage()
    ];
    echo json_encode($response);
}
