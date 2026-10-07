<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$type = isset($_GET['type']) ? $_GET['type'] : '';

try {
    $where = "";
    if ($type === 'stock') {
        $where = "WHERE action IN ('CREATE_MATERIAL', 'UPDATE_MATERIAL', 'DELETE_MATERIAL')";
    } else if ($type === 'in') {
        $where = "WHERE action = 'TRANSACTION_IN'";
    } else if ($type === 'out') {
        $where = "WHERE action = 'TRANSACTION_OUT'";
    } else if ($type === 'manage') {
        $where = "WHERE action IN ('DELETE_TRANSACTION', 'UPDATE_TRANSACTION')";
    }

    $stmt = $pdo2->query("SELECT * FROM pharmacy_logs $where ORDER BY id DESC LIMIT 100");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $logs
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'DB Error: ' . $e->getMessage()
    ]);
}
