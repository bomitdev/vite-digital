<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config.php';

try {
    $sql = "SELECT id, name, sort_order FROM qi_roles ORDER BY sort_order ASC, name ASC";
    $stmt = $pdo2->query($sql);
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $roles]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
