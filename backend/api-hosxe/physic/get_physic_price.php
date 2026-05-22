<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../../config.php';

try {
    if (!isset($db1)) {
        throw new Exception("Config variable \$db1 not found.");
    }

    // Connect to HOSxP Database (pdo1)
    // Note: We use charset=utf8 in DSN, but if data is raw TIS-620, we might need manual conversion.
    $pdo1 = new PDO("mysql:host={$db1['host']};dbname={$db1['name']};charset=utf8", $db1['user'], $db1['pass']);
    $pdo1->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get Parameters
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
    $end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
    $type       = isset($_GET['type']) ? $_GET['type'] : 'opd'; // 'opd' or 'ipd'

    $data = [];

    if ($type === 'opd') {
        // OPD Query
        $sql = "SELECT
	p.NAME AS pttype_name,
	COUNT( o.vn ) AS visit_count,
	COUNT( DISTINCT o.hn ) AS head_count,
	SUM( o.sum_price ) AS total_price 
FROM
	opitemrece o
	LEFT JOIN pttype p ON o.pttype = p.pttype 
WHERE
	o.vstdate BETWEEN :start_date AND :end_date
	AND o.income = '14'
	
GROUP BY
	o.pttype 
ORDER BY
	total_price DESC";

        $stmt = $pdo1->prepare($sql);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($type === 'ipd') {
        // IPD Query
        $sql = "SELECT
	p.NAME AS pttype_name,
	COUNT( DISTINCT o.an ) AS visit_count,
	COUNT( DISTINCT o.hn ) AS head_count,
	SUM( o.sum_price ) AS total_price 
FROM
	opitemrece o
	LEFT JOIN pttype p ON o.pttype = p.pttype 
WHERE
	o.rxdate BETWEEN :start_date AND :end_date
	AND o.income = '14'
	AND o.an IS NOT NULL
	AND o.an != ''
GROUP BY
	o.pttype 
ORDER BY
	total_price DESC";

        $stmt = $pdo1->prepare($sql);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Check and Convert Encoding
    foreach ($data as &$row) {
        if (isset($row['pttype_name'])) {
            // Try to detect if it's not UTF-8
            if (!mb_check_encoding($row['pttype_name'], 'UTF-8')) {
                $row['pttype_name'] = iconv('TIS-620', 'UTF-8', $row['pttype_name']);
            }
        }
    }
    unset($row);

    echo json_encode($data);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
