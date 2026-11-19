<?php
// backend/verify_otp.php

require_once "./config.php";
require_once "./auth_utils.php";
require_once "./audit_log_helper.php";

$data = json_decode(file_get_contents("php://input"), true);
$tempToken = $data['temp_token'] ?? '';
$otp = $data['otp'] ?? '';

if (empty($tempToken) || empty($otp)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ข้อมูลไม่ครบถ้วน"], JSON_UNESCAPED_UNICODE);
    exit;
}

// 1. Verify Temp Token
$tempPayload = verifyToken($tempToken);

if (!$tempPayload) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "ข้อมูลอ้างอิงไม่ถูกต้อง หรือถูกเปลี่ยนแปลง"], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. Check Expiration
if (isset($tempPayload['exp']) && time() > $tempPayload['exp']) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "รหัส OTP หมดอายุ กรุณาเข้าสู่ระบบใหม่"], JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. Verify OTP
$otpHash = $tempPayload['otp_hash'] ?? '';
if (isset($tempPayload['is_totp']) && $tempPayload['is_totp'] === true) {
    require_once "totp_utils.php";
    $g = new GoogleAuthenticator();
    $masterSecret = $_ENV['MASTER_ADMIN_TOTP_SECRET'] ?? 'JBSWY3DPEHPK3PXP';
    // Allow 2 intervals discrepancy (approx 1 minute window)
    if (!$g->verifyCode($masterSecret, $otp, 2)) {
        if (isset($pdo2)) {
            recordAuditLog($pdo2, [
                'user_id' => $tempPayload['uid'] ?? null,
                'username' => $tempPayload['user'] ?? null,
                'fullname' => $tempPayload['name'] ?? null,
                'module' => 'auth',
                'action' => 'LOGIN_OTP_FAIL',
                'status_code' => 401,
                'details' => 'ยืนยันรหัส Google Authenticator ไม่ถูกต้อง',
            ]);
        }
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "รหัสจาก Google Authenticator ไม่ถูกต้อง"], JSON_UNESCAPED_UNICODE);
        exit;
    }
} else {
    if (!password_verify($otp, $otpHash)) {
        if (isset($pdo2)) {
            recordAuditLog($pdo2, [
                'user_id' => $tempPayload['uid'] ?? null,
                'username' => $tempPayload['user'] ?? null,
                'fullname' => $tempPayload['name'] ?? null,
                'module' => 'auth',
                'action' => 'LOGIN_OTP_FAIL',
                'status_code' => 401,
                'details' => 'ยืนยันรหัส OTP ไม่ถูกต้อง',
            ]);
        }
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "รหัส OTP ไม่ถูกต้อง"], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 4. Create Final Session and Secure Token
session_start();
$_SESSION['user_id'] = $tempPayload['uid'];
$_SESSION['username'] = $tempPayload['user'];
$_SESSION['officer_name'] = $tempPayload['name'];
session_regenerate_id(true);

$finalPayload = [
    "uid" => $tempPayload['uid'],
    "user" => $tempPayload['user'],
    "iat" => time(),
    "exp" => time() + (24 * 60 * 60) // 24 hours
];

$generatedToken = generateToken($finalPayload);

if (isset($pdo2)) {
    recordAuditLog($pdo2, [
        'user_id' => $tempPayload['uid'] ?? null,
        'username' => $tempPayload['user'] ?? null,
        'fullname' => $tempPayload['name'] ?? null,
        'module' => 'auth',
        'action' => 'LOGIN_SUCCESS',
        'status_code' => 200,
        'details' => 'เข้าสู่ระบบสำเร็จ (ผ่านการยืนยัน OTP)',
    ], $pdo3 ?? null);
}

echo json_encode([
    "success" => true,
    "message" => "ยืนยันตัวตนสำเร็จ",
    "token" => $generatedToken,
    "user" => [
        "id" => $tempPayload['uid'],
        "username" => $tempPayload['user'],
        "name" => $tempPayload['name'],
        "position" => $tempPayload['position'] ?? '',
        "dept" => $tempPayload['dept'] ?? ''
    ]
], JSON_UNESCAPED_UNICODE);
