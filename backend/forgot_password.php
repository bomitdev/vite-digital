<?php
// backend/forgot_password.php
require_once "./config.php";

$data = json_decode(file_get_contents("php://input"), true);
$cid = $data['cid'] ?? '';

if (empty($cid)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "กรุณากรอกเลขบัตรประชาชน"], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Lookup user by CID
    $sql = "
        SELECT 
            ID,
            HR_USERNAME, 
            HR_PASSWORD, 
            MOPH_CLIENT_KEY, 
            MOPH_SECRET_KEY
        FROM hr_person
        WHERE HR_CID = :cid AND HR_STATUS_ID = '01'
        LIMIT 1
    ";

    $stmt = $pdo3->prepare($sql);
    $stmt->execute(['cid' => $cid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "ไม่พบข้อมูลผู้ใช้งาน หรือบัญชีถูกระงับ"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $clientKey = $user['MOPH_CLIENT_KEY'] ?? '';
    $secretKey = $user['MOPH_SECRET_KEY'] ?? '';

    if (empty($clientKey) || empty($secretKey)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "ผู้ใช้งานนี้ไม่ได้ตั้งค่าระบบแจ้งเตือน (MOPH_CLIENT_KEY / MOPH_SECRET_KEY)"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $username = $user['HR_USERNAME'];
    $userId = $user['ID'];

    // Set default password to '123'
    $newPassword = '123';
    $hashedPassword = md5($newPassword);

    // Update password in db
    $updateSql = "UPDATE hr_person SET HR_PASSWORD = :password WHERE ID = :id";
    $updateStmt = $pdo3->prepare($updateSql);
    $updateStmt->execute([
        ':password' => $hashedPassword,
        ':id' => $userId
    ]);

    // Send the clear text password
    $password = $newPassword;

    // Construct Flex Message payload mimicking Line Messaging API used by MOPH
    $linePayload = [
        "messages" => [
            [
                "type" => "flex",
                "altText" => "แจ้งเตือนลืมรหัสผ่าน",
                "contents" => [
                    "type" => "bubble",
                    "direction" => "ltr",
                    "header" => [
                        "type" => "box",
                        "layout" => "vertical",
                        "backgroundColor" => "#6f42c1",
                        "contents" => [
                            [
                                "type" => "text",
                                "text" => "🔐 ข้อมูลเข้าสู่ระบบ",
                                "weight" => "bold",
                                "size" => "lg",
                                "color" => "#ffffff",
                                "align" => "center"
                            ]
                        ]
                    ],
                    "body" => [
                        "type" => "box",
                        "layout" => "vertical",
                        "contents" => [
                            [
                                "type" => "box",
                                "layout" => "baseline",
                                "spacing" => "sm",
                                "contents" => [
                                    ["type" => "text", "text" => "Username", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                    ["type" => "text", "text" => $username, "wrap" => true, "weight" => "bold", "color" => "#333333", "size" => "md", "flex" => 4]
                                ]
                            ],
                            [
                                "type" => "box",
                                "layout" => "baseline",
                                "spacing" => "sm",
                                "contents" => [
                                    ["type" => "text", "text" => "Password", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                    ["type" => "text", "text" => $password, "wrap" => true, "weight" => "bold", "color" => "#333333", "size" => "md", "flex" => 4]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    // Send via MOPH Notify API
    $url = "https://morpromt2f.moph.go.th/api/notify/send";
    $headers = [
        "Content-Type: application/json",
        "client-key: {$clientKey}",
        "secret-key: {$secretKey}"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($linePayload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $result = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);


    if ($err) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "ส่งการแจ้งเตือนไม่สำเร็จ: " . $err], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($httpCode !== 200) {
        http_response_code(400);
        // Sometimes $result has JSON, sometimes not
        $resObj = json_decode($result, true);
        $errMsg = $resObj['message'] ?? 'HTTP Code ' . $httpCode;
        echo json_encode(["success" => false, "message" => "ส่งการแจ้งเตือนไม่ซำเร็จ: " . $errMsg], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode(["success" => true, "message" => "ส่งข้อมูลบัญชีผู้ใช้ไปทาง Line สำเร็จ!"], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "ฐานข้อมูลขัดข้อง: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
