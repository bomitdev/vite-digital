<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

try {
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '2024-10-01';
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '2025-09-30';

    $stmt = $pdo1->prepare("
        SELECT
            COALESCE(vn.pttype, '') AS pttype,
            COALESCE(NULLIF(TRIM(p.name), ''), CONCAT('สิทธิ ', COALESCE(vn.pttype, 'ไม่ระบุ'))) AS pttype_name,
            COUNT(DISTINCT vn.vn) AS visit_count,
            COUNT(DISTINCT vn.hn) AS person_count,
            COALESCE(SUM(vn.income), 0) AS total_income
        FROM vn_stat vn
        LEFT JOIN pttype p ON vn.pttype = p.pttype
        WHERE vn.vstdate BETWEEN :start_date AND :end_date
        GROUP BY vn.pttype, p.name
        ORDER BY visit_count DESC, total_income DESC
    ");
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($data as &$row) {
        if (isset($row['pttype_name']) && !mb_check_encoding($row['pttype_name'], 'UTF-8')) {
            $row['pttype_name'] = iconv('TIS-620', 'UTF-8//IGNORE', $row['pttype_name']);
        }
        $row['visit_count'] = (int)$row['visit_count'];
        $row['person_count'] = (int)$row['person_count'];
        $row['total_income'] = (float)$row['total_income'];
    }
    unset($row);

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

