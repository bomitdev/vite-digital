<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require '../config.php';
require_once __DIR__ . '/../auth_utils.php';

// Secure Auth
$userData = authGuard();
$user_id = $userData['uid'];

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->data_id)) {
    try {
        $stmt = $pdo3->prepare("DELETE FROM 10985_data_report WHERE data_id = :id");
        if ($stmt->execute(['id' => $data->data_id])) {
            echo json_encode(["status" => "success", "message" => "Deleted successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to delete"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "DB Error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Missing ID"]);
}
