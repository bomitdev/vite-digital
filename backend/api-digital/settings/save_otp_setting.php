<?php
require_once "../../config.php";
require_once "../../auth_utils.php";

$userData = authGuard();
$user_id = $userData['uid'] ?? 0;

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid user data']);
    exit;
}

// Check admin role from DB
$stmt = $pdo3->prepare("SELECT access_user FROM hr_person WHERE ID = :id");
$stmt->bindParam(":id", $user_id);
$stmt->execute();
$userDb = $stmt->fetch(PDO::FETCH_ASSOC);

$access = $userDb['access_user'] ?? '';
$rights = explode(':', $access);
$isAdmin = in_array('Super', $rights) || in_array('Admin', $rights) || in_array('administrator', $rights);

if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden: You do not have permission to change this setting']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['is_otp_enabled'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    exit;
}

$settingsFile = __DIR__ . '/../../config_settings.json';
$settings = [
    'is_otp_enabled' => (bool)$data['is_otp_enabled']
];

if (file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT))) {
    echo json_encode(['status' => 'success', 'message' => 'Settings saved successfully']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to save settings']);
}
