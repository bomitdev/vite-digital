<?php
session_start();


// Config now handles CORS and Autoload
require_once "./config.php";
require_once "./auth_utils.php";

$data = json_decode(file_get_contents("php://input"), true);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "กรุณากรอก username และ password"], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $sql = "
        SELECT 
            p.ID AS officer_id, 
            p.HR_USERNAME AS officer_login_name,
            CONCAT(f.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) AS officer_name,
            p.HR_PASSWORD AS officer_login_password_md5,
            hp.HR_POSITION_NAME,
            hds.HR_DEPARTMENT_SUB_NAME,
            p.HR_STATUS_ID
        FROM hr_person p
        LEFT JOIN hr_prefix f ON p.HR_PREFIX_ID = f.HR_PREFIX_ID
        LEFT JOIN hr_position hp ON hp.HR_POSITION_ID = p.HR_POSITION_ID
        LEFT JOIN hr_department_sub hds ON p.HR_DEPARTMENT_SUB_ID = hds.HR_DEPARTMENT_SUB_ID
        WHERE p.HR_USERNAME = :username AND p.HR_STATUS_ID = '01'
        LIMIT 1
    ";

    $stmt = $pdo3->prepare($sql);
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "ไม่พบผู้ใช้งาน หรือบัญชีถูกระงับ"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Password verification logic
    $isValid = false;
    $storedPass = $user['officer_login_password_md5'];

    // Check if it's a modern hash or legacy MD5
    if (strpos($storedPass, '$2y$') === 0) {
        // Modern Bcrypt hash
        $isValid = password_verify($password, $storedPass);
    } else {
        // Legacy MD5 fallback
        $isValid = (strtoupper(md5($password)) === strtoupper($storedPass));
    }

    if (!$isValid) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "รหัสผ่านไม่ถูกต้อง"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 4. Create Session and Secure Token
    $_SESSION['user_id'] = $user['officer_id'];
    $_SESSION['username'] = $user['officer_login_name'];
    $_SESSION['officer_name'] = $user['officer_name'];
    session_regenerate_id(true);

    $tokenPayload = [
        "uid" => $user['officer_id'],
        "user" => $user['officer_login_name'],
        "iat" => time(),
        "exp" => time() + (24 * 60 * 60) // 24 hours
    ];

    // Secure signed token
    $generatedToken = generateToken($tokenPayload);

    echo json_encode([
        "success" => true,
        "message" => "เข้าสู่ระบบสำเร็จ",
        "token" => $generatedToken,
        "user" => [
            "id" => $user['officer_id'],
            "username" => $user['officer_login_name'],
            "name" => $user['officer_name'],
            "position" => $user['HR_POSITION_NAME'],
            "dept" => $user['HR_DEPARTMENT_SUB_NAME']
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "ฐานข้อมูลขัดข้อง"], JSON_UNESCAPED_UNICODE);
}
