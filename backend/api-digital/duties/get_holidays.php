<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';

header("Content-Type: application/json; charset=utf-8");

try {
    $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
    if ($year > 2400) {
        $year -= 543;
    }
    $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
    $prefix = sprintf('%04d-%02d-%%', $year, $month);

    $pdo = $pdo1 ?? ($pdo3 ?? null);
    if (!$pdo) {
        echo json_encode(['status' => 'success', 'data' => new stdClass()]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT DAY(holiday_date) AS day_num, COALESCE(NULLIF(TRIM(day_name), ''), 'วันหยุด') AS day_name
        FROM holiday
        WHERE holiday_date LIKE ?
        ORDER BY holiday_date ASC
    ");
    $stmt->execute([$prefix]);

    $map = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $d = (string)((int)$row['day_num']);
        $name = trim((string)$row['day_name']);
        if ($name !== '' && !mb_check_encoding($name, 'UTF-8')) {
            $name = iconv('TIS-620', 'UTF-8//IGNORE', $name);
        }
        $map[$d] = $name;
    }

    echo json_encode(['status' => 'success', 'data' => (object)$map], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'data' => new stdClass()]);
}

