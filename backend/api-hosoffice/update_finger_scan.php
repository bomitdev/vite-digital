<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth_utils.php';

// Secure Auth
$userData = authGuard();
$user_id = $userData['uid'];


try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['id']) || empty($data['time']) || empty($data['type']) || empty($data['date'])) {
        echo json_encode(["status" => "error", "message" => "Incomplete data"]);
        exit;
    }

    $id = $data['id'];
    $new_time = $data['time']; // HH:mm
    $new_type = $data['type']; // C/In or C/Out
    $date = $data['date']; // YYYY-MM-DD

    // Combine date and time
    $new_datetime = "$date $new_time:00";

    $sql = "UPDATE `10985_hos_fingerscan` 
            SET fingerscan_datetime = :dt, 
                fingerscan_inout = :type 
            WHERE fingerscan_id = :id";

    $stmt = $pdo3->prepare($sql);
    $result = $stmt->execute([
        ':dt' => $new_datetime,
        ':type' => $new_type,
        ':id' => $id
    ]);

    if ($result) {
        echo json_encode(["status" => "success", "message" => "Updated successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Update failed"]);
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Error: " . $e->getMessage()]);
}
