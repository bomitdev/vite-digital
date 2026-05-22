<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth_utils.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->target_user_id) || !isset($data->new_access)) {
    echo json_encode(["status" => "error", "message" => "Missing parameters"]);
    exit;
}

// Secure Auth
$userData = authGuard();
$requester_id = $userData['uid'];

if (!$requester_id) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

try {
    // 1. Verify Requester is Super/Admin
    // Fetch requester's access IDs
    $checkSql = "SELECT access_user FROM hr_person WHERE ID = :id";
    $stmt = $pdo3->prepare($checkSql);
    $stmt->bindParam(":id", $requester_id);
    $stmt->execute();
    $requester = $stmt->fetch(PDO::FETCH_ASSOC);

    $requester_access_ids = isset($requester['access_user']) ? explode(':', $requester['access_user']) : [];

    // Fetch IDs for allowed roles
    $roleSql = "SELECT access_id FROM `10985_hos_access` WHERE access_name IN ('Super', 'Admin', 'administrator')";
    $stmt = $pdo3->prepare($roleSql);
    $stmt->execute();
    $allowed_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Check if requester has any of the allowed IDs OR allowed Names (Legacy Support)
    $has_permission = false;
    $allowed_names = ['Super', 'Admin', 'administrator'];

    foreach ($requester_access_ids as $token) {
        // Check ID match
        if (in_array($token, $allowed_ids)) {
            $has_permission = true;
            break;
        }
        // Check Name match (Legacy)
        if (in_array($token, $allowed_names)) {
            $has_permission = true;
            break;
        }
    }

    if (!$has_permission) {
        echo json_encode(["status" => "error", "message" => "Permission Denied: Only Super or administrator users can manage rights."]);
        exit;
    }

    // 2. Update Target User
    $updateSql = "UPDATE hr_person SET access_user = :access, FINGLE_ID = :finger_id WHERE ID = :target_id";
    $stmt = $pdo3->prepare($updateSql);
    $stmt->bindParam(":access", $data->new_access);

    // Handle finger_id (allow null or empty string)
    $finger_id = isset($data->finger_id) ? $data->finger_id : null;
    $stmt->bindParam(":finger_id", $finger_id);

    $stmt->bindParam(":target_id", $data->target_user_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Access rights updated successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to update database."]);
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
