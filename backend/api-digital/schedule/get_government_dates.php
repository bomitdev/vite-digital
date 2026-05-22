<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

try {
    $month = isset($_GET['month']) ? $_GET['month'] : date('m');
    $year = isset($_GET['year']) ? $_GET['year'] : date('Y');

    $sql = "SELECT * FROM 10985_hos_government_date 
            WHERE MONTH(gov_date) = :month AND YEAR(gov_date) = :year 
            ORDER BY gov_date ASC";

    $stmt = $pdo3->prepare($sql);
    $stmt->execute([':month' => $month, ':year' => $year]);
    $dates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $dates]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
