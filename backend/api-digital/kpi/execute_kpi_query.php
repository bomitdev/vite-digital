<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';

session_start();

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    $userData = authOptional();
    if ($userData) {
        if (isset($userData['uid'])) {
            $user_id = $userData['uid'];
        } elseif (isset($userData['data']['id'])) {
            $user_id = $userData['data']['id'];
        }
    }
}

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$kpi_id = $data['kpi_id'] ?? null;
$start_date = $data['start_date'] ?? null;
$end_date = $data['end_date'] ?? null;
$department_id = $data['department_id'] ?? 'ALL';

if (!$kpi_id) {
    echo json_encode(['status' => 'error', 'message' => 'KPI ID is required']);
    exit;
}

try {
    $stmt = $pdo2->prepare("SELECT sql_query, db_connection FROM kpi_definitions WHERE id = :id");
    $stmt->execute([':id' => $kpi_id]);
    $kpi = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$kpi || empty($kpi['sql_query'])) {
        echo json_encode(['status' => 'error', 'message' => 'KPI does not have an SQL query configured']);
        exit;
    }

    $sql_query = $kpi['sql_query'];
    $db_conn_id = $kpi['db_connection'];

    // Select target database
    $target_pdo = $pdo1;
    if ($db_conn_id == 2) $target_pdo = $pdo2;
    elseif ($db_conn_id == 3) $target_pdo = $pdo3;

    // Bind default parameters
    $params = [];
    if (strpos($sql_query, ':start_date') !== false) {
        if (!$start_date) throw new Exception("start_date is required for this query");
        $params[':start_date'] = $start_date;
    }
    if (strpos($sql_query, ':end_date') !== false) {
        if (!$end_date) throw new Exception("end_date is required for this query");
        $params[':end_date'] = $end_date;
    }
    if (strpos($sql_query, ':department') !== false) {
        $params[':department'] = $department_id;
    }

    // Auto-bind any other custom placeholders with a dummy value just in case
    if (preg_match_all('/:([a-zA-Z0-9_]+)/', $sql_query, $matches)) {
        foreach ($matches[1] as $param_name) {
            $placeholder = ':' . $param_name;
            if (!array_key_exists($placeholder, $params)) {
                $params[$placeholder] = 'TEST';
            }
        }
    }

    $target_pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    $exec_stmt = $target_pdo->prepare($sql_query);
    $exec_stmt->execute($params);
    $result = $exec_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        // Query ran but returned no rows
        echo json_encode(['status' => 'success', 'data' => []]);
    } else {
        echo json_encode(['status' => 'success', 'data' => $result]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
