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
            p.HR_CID,
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

    // --- NEW: Check Global OTP Setting ---
    $settingsFile = __DIR__ . '/config_settings.json';
    $isOtpEnabled = true;
    if (file_exists($settingsFile)) {
        $settings = json_decode(file_get_contents($settingsFile), true);
        if (isset($settings['is_otp_enabled'])) {
            $isOtpEnabled = (bool)$settings['is_otp_enabled'];
        }
    }

    $masterAdminUsernames = explode(',', $_ENV['MASTER_ADMIN_USERNAME'] ?? 'admin');
    $masterAdminUsernames = array_map('trim', $masterAdminUsernames);
    $isMasterAdmin = in_array($user['officer_login_name'], $masterAdminUsernames);

    // If OTP is globally disabled, log in directly
    if (!$isOtpEnabled) {
        $payload = [
            "uid" => $user['officer_id'],
            "user" => $user['officer_login_name'],
            "name" => $user['officer_name'],
            "position" => $user['HR_POSITION_NAME'],
            "dept" => $user['HR_DEPARTMENT_SUB_NAME'],
            "iat" => time(),
            "exp" => time() + (3600 * 8)
        ];

        $generatedToken = generateToken($payload);

        $_SESSION['user_id'] = $user['officer_id'];
        $_SESSION['username'] = $user['officer_login_name'];
        $_SESSION['officer_name'] = $user['officer_name'];
        session_regenerate_id(true);

        echo json_encode([
            "success" => true,
            "message" => "เข้าสู่ระบบสำเร็จ (OTP Disabled)",
            "token" => $generatedToken,
            "user" => [
                "id" => $user['officer_id'],
                "username" => $user['officer_login_name'],
                "name" => $user['officer_name'],
                "position" => $user['HR_POSITION_NAME'],
                "dept" => $user['HR_DEPARTMENT_SUB_NAME']
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // If Master Admin, require TOTP instead of Mohpromt
    if ($isMasterAdmin) {
        $tempPayload = [
            "uid" => $user['officer_id'],
            "user" => $user['officer_login_name'],
            "name" => $user['officer_name'],
            "position" => $user['HR_POSITION_NAME'],
            "dept" => $user['HR_DEPARTMENT_SUB_NAME'],
            "is_totp" => true,
            "iat" => time(),
            "exp" => time() + 300 // 5 mins for TOTP
        ];
        $tempToken = generateToken($tempPayload);
        
        echo json_encode([
            "success" => true,
            "require_otp" => true,
            "is_totp" => true,
            "message" => "กรุณายืนยันรหัสจาก Google Authenticator",
            "temp_token" => $tempToken
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 3. Instead of logging in directly, we require Mohpromt 2FA
    $cid = trim($user['HR_CID'] ?? '');
    
    if (empty($cid) || strlen(preg_replace('/[^0-9]/', '', $cid)) !== 13) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "ผู้ใช้นี้ยังไม่มีข้อมูลเลขบัตรประชาชน (CID) หรือข้อมูลไม่ถูกต้อง ไม่สามารถส่ง OTP ได้ กรุณาติดต่อผู้ดูแลระบบ"], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $cid = preg_replace('/[^0-9]/', '', $cid);

    // Generate 6 digit OTP
    $otp = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);

    // Send OTP via Mohpromt API
    $mohpromtUrl = rtrim($_ENV['MOHPROMT_API_URL'] ?? 'https://morpromt2c.moph.go.th/alert/v3.1/messages', '/');
    if (!str_ends_with($mohpromtUrl, '/alert/v3.1/messages') && !str_ends_with($mohpromtUrl, '/messages')) {
        $mohpromtUrl .= '/alert/v3.1/messages';
    }
    
    $clientKey = $_ENV['MOHPROMT_CLIENT_KEY'] ?? '';
    $secretKey = $_ENV['MOHPROMT_SECRET_KEY'] ?? '';

    date_default_timezone_set('Asia/Bangkok');
    $timeStr = date('H:i');
    $dateStr = date('d/m/Y');

    $payload = [
        "cids" => [$cid],
        "message_title" => "E-Office OTP",
        "message_text" => "แจ้ง E-Office OTP [$otp]",
        "message_html" => "<b>รหัส OTP ของคุณคือ: $otp</b>",
        "message_type" => "1",
        "flex_message" => [
            "type" => "flex",
            "altText" => "รหัส OTP เข้าสู่ระบบคือ {$otp}",
            "contents" => [
                "type" => "bubble",
                "body" => [
                    "type" => "box",
                    "layout" => "vertical",
                    "contents" => [
                        [
                            "type" => "text",
                            "text" => "แจ้ง E-Office OTP [{$otp}]",
                            "weight" => "bold",
                            "color" => "#1DB446",
                            "size" => "md",
                            "align" => "center"
                        ],
                        [
                            "type" => "text",
                            "text" => "ณ เวลา {$timeStr} น.",
                            "size" => "xs",
                            "color" => "#aaaaaa",
                            "align" => "center",
                            "margin" => "sm"
                        ],
                        [
                            "type" => "separator",
                            "margin" => "md"
                        ],
                        [
                            "type" => "box",
                            "layout" => "vertical",
                            "margin" => "md",
                            "spacing" => "sm",
                            "contents" => [
                                [
                                    "type" => "box",
                                    "layout" => "horizontal",
                                    "contents" => [
                                        [
                                            "type" => "text",
                                            "text" => "คุณกำลังเข้าใช้งานระบบ",
                                            "size" => "xs",
                                            "color" => "#888888",
                                            "flex" => 1
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        [
                            "type" => "box",
                            "layout" => "vertical",
                            "margin" => "lg",
                            "contents" => [
                                [
                                    "type" => "text",
                                    "text" => "โปรดกรอกรหัส",
                                    "size" => "sm",
                                    "align" => "center",
                                    "color" => "#444444"
                                ],
                                [
                                    "type" => "text",
                                    "text" => "{$otp}",
                                    "size" => "3xl",
                                    "weight" => "bold",
                                    "color" => "#1DB446",
                                    "align" => "center",
                                    "letterSpacing" => "10px"
                                ],
                                [
                                    "type" => "text",
                                    "text" => "<รหัสมีอายุ 1 นาที>",
                                    "size" => "xs",
                                    "color" => "#aaaaaa",
                                    "align" => "center"
                                ]
                            ]
                        ],
                        [
                            "type" => "box",
                            "layout" => "vertical",
                            "margin" => "lg",
                            "contents" => [
                                [
                                    "type" => "text",
                                    "text" => "เพื่อความปลอดภัยกรุณาเก็บเป็นความลับ",
                                    "size" => "xs",
                                    "color" => "#666666",
                                    "align" => "center"
                                ],
                                [
                                    "type" => "text",
                                    "text" => "ห้ามเปิดเผยรหัส OTP แก่บุคคลอื่นไม่ว่ากรณีใดๆ",
                                    "size" => "xs",
                                    "color" => "#666666",
                                    "align" => "center"
                                ]
                            ]
                        ],
                        [
                            "type" => "separator",
                            "margin" => "md"
                        ],
                        [
                            "type" => "box",
                            "layout" => "vertical",
                            "margin" => "md",
                            "contents" => [
                                [
                                    "type" => "text",
                                    "text" => "Digital Health V.{$dateStr}",
                                    "size" => "xxs",
                                    "color" => "#cccccc",
                                    "align" => "center"
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    $ch = curl_init($mohpromtUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'client-key: ' . $clientKey,
        'secret-key: ' . $secretKey
    ]);
    
    // Disable SSL verification for development if needed
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    $resData = json_decode($response, true);

    // MOPH API Bug Workaround:
    // Some users (like Sirilak) return "No Cid" for line_message when using "cids" + flex_message.
    // BUT they successfully receive messages when using "cid" (singular) + "messages" (text).
    // If the primary Flex payload fails for LINE, we send a fallback text payload.
    if ($httpCode >= 200 && $httpCode < 300 && isset($resData['line_message']) && $resData['line_message'] === 'No Cid') {
        $fallbackPayload = [
            "cid" => [$cid],
            "messages" => [
                [
                    "type" => "text",
                    "text" => "รหัส OTP สำหรับเข้าสู่ระบบ E-Office\n\nรหัส OTP ของคุณคือ: " . $otp . "\nรหัสมีอายุ 1 นาที"
                ]
            ]
        ];
        
        $ch2 = curl_init($mohpromtUrl);
        curl_setopt($ch2, CURLOPT_POST, 1);
        curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($fallbackPayload, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'client-key: ' . $clientKey,
            'secret-key: ' . $secretKey
        ]);
        curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, false);
        
        curl_exec($ch2);
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        // 4. Create Temporary JWT with OTP Hash (Stateless)
        $tempPayload = [
            "uid" => $user['officer_id'],
            "user" => $user['officer_login_name'],
            "name" => $user['officer_name'],
            "position" => $user['HR_POSITION_NAME'],
            "dept" => $user['HR_DEPARTMENT_SUB_NAME'],
            "otp_hash" => password_hash($otp, PASSWORD_BCRYPT),
            "iat" => time(),
            "exp" => time() + 60 // Expire in 1 min
        ];

        $tempToken = generateToken($tempPayload);

        echo json_encode([
            "success" => true,
            "require_otp" => true,
            "message" => "กรุณายืนยันรหัส OTP ที่ส่งไปยังหมอพร้อม",
            "temp_token" => $tempToken
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "ไม่สามารถส่งรหัส OTP ได้ กรุณาลองใหม่อีกครั้ง หรือติดต่อผู้ดูแลระบบ", "debug" => $response], JSON_UNESCAPED_UNICODE);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "ฐานข้อมูลขัดข้อง"], JSON_UNESCAPED_UNICODE);
}
