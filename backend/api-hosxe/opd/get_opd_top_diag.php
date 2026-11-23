<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

try {
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '2024-10-01';
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '2025-09-30';
    $limit = isset($_GET['limit']) ? max(10, min(500, (int)$_GET['limit'])) : 200;

    $stmt = $pdo1->prepare("
        SELECT
            TRIM(vn.pdx) AS pdx,
            COALESCE(NULLIF(TRIM(i.name), ''), TRIM(vn.pdx)) AS icd_name,
            COALESCE(NULLIF(TRIM(i.tname), ''), '') AS icd_tname,
            COUNT(DISTINCT vn.vn) AS visit_count,
            COUNT(DISTINCT vn.hn) AS person_count,
            COALESCE(SUM(vn.income), 0) AS total_income
        FROM vn_stat vn
        LEFT JOIN icd101 i ON vn.pdx = i.code
        WHERE vn.vstdate BETWEEN :start_date AND :end_date
          AND vn.pdx IS NOT NULL
          AND TRIM(vn.pdx) <> ''
        GROUP BY TRIM(vn.pdx), i.name, i.tname
        ORDER BY visit_count DESC, person_count DESC
        LIMIT {$limit}
    ");
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($data as &$row) {
        foreach (['icd_name', 'icd_tname'] as $col) {
            if (isset($row[$col]) && $row[$col] !== '' && !mb_check_encoding($row[$col], 'UTF-8')) {
                $row[$col] = iconv('TIS-620', 'UTF-8//IGNORE', $row[$col]);
            }
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

