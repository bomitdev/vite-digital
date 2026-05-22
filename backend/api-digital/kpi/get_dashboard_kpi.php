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

// join ล่าสุดของแต่ละ KPI
$sql = "
SELECT k.id, k.kpi_name, k.denominator, k.target, r.calc_percent
FROM kpi_items k
LEFT JOIN (
    SELECT kpi_id, calc_percent
    FROM kpi_results
    WHERE result_date = CURDATE()
    GROUP BY kpi_id
) r ON r.kpi_id = k.id
";
$stmt = $pdo2->query($sql);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
