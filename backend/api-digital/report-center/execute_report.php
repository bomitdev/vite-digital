<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require '../../config.php';

$data = json_decode(file_get_contents("php://input"), true);
$report_id = $data['report_id'] ?? null;

if (!$report_id) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Report ID is required."]);
    exit;
}

try {
    // 1. Get the query details from metadata DB (pdo2)
    $stmt = $pdo2->prepare("SELECT title, sql_query, db_connection FROM report_queries WHERE id = :id");
    $stmt->execute([':id' => $report_id]);
    $report = $stmt->fetch();

    if (!$report) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Report not found."]);
        exit;
    }

    $sql_query = $report['sql_query'];
    $db_conn_id = $report['db_connection'];

    // --- Strip Custom DSL Lines from SQL Query ---
    $sql_lines = explode("\n", $sql_query);
    $actual_sql = [];
    foreach ($sql_lines as $line) {
        if (preg_match('/^:[a-zA-Z0-9_]+\s*=\s*SELECT/i', trim($line))) {
            continue; // Skip this line as it is a DSL parameter definition
        }
        $actual_sql[] = $line;
    }
    $sql_query = implode("\n", $actual_sql);

    // 2. Select the target database connection
    $target_pdo = null;
    if ($db_conn_id == 1) $target_pdo = $pdo1;
    elseif ($db_conn_id == 2) $target_pdo = $pdo2;
    elseif ($db_conn_id == 3) $target_pdo = $pdo3;
    else $target_pdo = $pdo1; // Default

    // 3. Execute the query on the target DB
    // Security Note: In a real app, we should validate this SQL further, but for an Admin-created report tool, we assume Admin knows SQL.
    // However, for read-only safety, we might try to enforce SELECT only, but complex reports might need temp tables. 
    // For now, allow raw execution as requested.

    $params = [];
    $start_date = $data['start_date'] ?? null;
    $end_date = $data['end_date'] ?? null;

    // Check for placeholders in SQL
    // We look for :start_date and :end_date
    if ($start_date && strpos($sql_query, ':start_date') !== false) {
        $params[':start_date'] = $start_date;
    }
    // If param exists in query but user didn't send it, we might error or bind null. 
    // Usually better to error or provide default? For now let's hope user provides it.

    if ($end_date && strpos($sql_query, ':end_date') !== false) {
        // If it's a date range inclusive of time, user might only send Y-m-d.
        // If the query is just date, it's fine. If datetime, we might want to append time.
        // But let's assume raw string binding for flexibility.
        $params[':end_date'] = $end_date;
    }

    $department_id = $data['department_id'] ?? 'ALL';
    if (strpos($sql_query, ':department') !== false) {
        $params[':department'] = $department_id;
    }

    // Bind Dynamic Parameters
    $dynamic_params = $data['parameters'] ?? [];
    if (is_array($dynamic_params)) {
        foreach ($dynamic_params as $key => $value) {
            if (strpos($sql_query, ':' . $key) !== false) {
                // Only bind if it isn't already bound (e.g., start_date)
                if (!array_key_exists(':' . $key, $params)) {
                    $params[':' . $key] = $value;
                }
            }
        }
    }

    $target_pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    $query_stmt = $target_pdo->prepare($sql_query);
    $query_stmt->execute($params);
    $results = $query_stmt->fetchAll();

    // Get column names if results exist
    $columns = [];
    if (!empty($results)) {
        $columns = array_keys($results[0]);
    } else {
        // If no results, try to get column meta if possible, or just return empty
        // PDO doesn't easily give column meta for empty result set without specific driver support, 
        // but for now empty columns is fine.
    }

    echo json_encode([
        "success" => true,
        "report_title" => $report['title'],
        "columns" => $columns,
        "data" => $results
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Execution error: " . $e->getMessage()]);
}
