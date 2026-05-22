<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

require '../../config.php';

try {
    $pdo1 = new PDO("mysql:host={$db1['host']};dbname={$db1['name']};charset=utf8", $db1['user'], $db1['pass']);
    $pdo1->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

    $stmt = $pdo1->prepare("
        SELECT 
            COALESCE(h.name, ro.refer_hospcode) as hospname,
            COUNT(DISTINCT ro.vn) as refer_count
        FROM vn_stat vn
        INNER JOIN referout ro ON vn.vn = ro.vn
        LEFT JOIN hospcode h ON ro.refer_hospcode = h.hospcode
        WHERE vn.vstdate BETWEEN :start_date AND :end_date
        GROUP BY ro.refer_hospcode
        ORDER BY refer_count DESC
        LIMIT 5
    ");

    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
