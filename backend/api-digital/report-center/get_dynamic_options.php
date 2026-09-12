<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require '../../config.php';

$report_id = $_GET['report_id'] ?? null;
$param_name = $_GET['param_name'] ?? null;

if (!$report_id || !$param_name) {
    http_response_code(400);
    echo json_encode(["error" => "Missing report_id or param_name"]);
    exit;
}

try {
    // ดึง Config ของรายงาน
    $stmt = $pdo2->prepare("SELECT sql_query, parameters, db_connection FROM report_queries WHERE id = :id");
    $stmt->execute([':id' => $report_id]);
    $report = $stmt->fetch();

    if (!$report) {
        echo json_encode([]);
        exit;
    }

    $parameters = !empty($report['parameters']) ? json_decode($report['parameters'], true) : [];
    $query_to_run = null;

    // หา parameter ที่ตรงกับชื่อที่ขอมาใน JSON ก่อน
    if (is_array($parameters)) {
        foreach ($parameters as $param) {
            if ($param['name'] === $param_name && !empty($param['query'])) {
                $query_to_run = $param['query'];
                break;
            }
        }
    }

    // ถ้าไม่เจอใน JSON ให้ลองหาในบรรทัดของ sql_query (Smart SQL Parser)
    if (!$query_to_run && !empty($report['sql_query'])) {
        $lines = explode("\n", $report['sql_query']);
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^:([a-zA-Z0-9_]+)\s*=\s*(SELECT\s+.*)/i', $line, $matches)) {
                if ($matches[1] === $param_name) {
                    $query_to_run = $matches[2];
                    break;
                }
            }
        }
    }

    if (!$query_to_run) {
        echo json_encode([]);
        exit;
    }

    // เลือกว่าจะ Query บนฐานข้อมูลไหน
    $target_pdo = $pdo1; // Default
    if ($report['db_connection'] == 2) $target_pdo = $pdo2;
    elseif ($report['db_connection'] == 3) $target_pdo = $pdo3;

    // รัน Query ที่ถูกเก็บไว้ใน JSON
    $options_stmt = $target_pdo->query($query_to_run);
    $options = $options_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
