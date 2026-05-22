<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require '../config.php';

// รับข้อมูล JSON
$data = json_decode(file_get_contents("php://input"));

if (
    !empty($data->data_name)
) {
    $data_name = $data->data_name;
    $data_column = $data->data_column ?? '';
    $reason_id = $data->reason_id ?? 1;
    $reason_other = $data->reason_other ?? '';
    $data_type_id = $data->data_type_id ?? 'รายครั้ง';
    $data_date = $data->data_date ?? '';
    $file_type = $data->file_type ?? 'Excel';
    $data_receive = $data->data_receive ?? 'ส่งผ่าน Email/Line';
    $want_date = $data->want_date ?? NULL;
    $remark = $data->remark ?? '';

    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../auth_utils.php';

    // Secure Auth
    $userData = authGuard();
    $crt_by = $userData['uid'];

    // Current Time
    $crt_date = date("Y-m-d H:i:s");

    // Status = 1 (Pending)
    $data_status_id = 1;

    try {
        $sql = "INSERT INTO 10985_data_report
                (data_name, data_column, reason_id, reason_other, data_type_id, data_date, file_type, data_receive, want_date, remark, crt_by, crt_date, data_status_id)
                VALUES
                (:data_name, :data_column, :reason_id, :reason_other, :data_type_id, :data_date, :file_type, :data_receive, :want_date, :remark, :crt_by, :crt_date, :data_status_id)";

        $stmt = $pdo3->prepare($sql);

        // Bind parameters
        $stmt->bindParam(":data_name", $data_name);
        $stmt->bindParam(":data_column", $data_column);
        $stmt->bindParam(":reason_id", $reason_id);
        $stmt->bindParam(":reason_other", $reason_other);
        $stmt->bindParam(":data_type_id", $data_type_id);
        $stmt->bindParam(":data_date", $data_date);
        $stmt->bindParam(":file_type", $file_type);
        $stmt->bindParam(":data_receive", $data_receive);
        $stmt->bindParam(":want_date", $want_date);
        $stmt->bindParam(":remark", $remark);
        $stmt->bindParam(":crt_by", $crt_by);
        $stmt->bindParam(":crt_date", $crt_date);
        $stmt->bindParam(":data_status_id", $data_status_id);

        if ($stmt->execute()) {

            // bom
            //$client_key = '3f8507778fc4d54b3735d49935ac6673c5b70ef5';
            //$secret_key = 'AQNMNEIJGNUSWISSHGDHQH343ZHA';

            // moph alert hc10985
            $client_key = '151518fbb68266eb98604e010d4ffdb231365f32';
            $secret_key = 'KVQRE5QY6PEDRAQRX6UGQU7QOO5Y';

            // Construct Flex Message
            // Fetch Sender Name
            $stmtUser = $pdo3->prepare("SELECT CONCAT(HR_FNAME, ' ', HR_LNAME) as fullname FROM hr_person WHERE id = :id");
            $stmtUser->execute([':id' => $crt_by]);
            $user_info = $stmtUser->fetch(PDO::FETCH_ASSOC);
            $senderName = $user_info['fullname'] ?? 'Unknown User';
            $dateText = $want_date ?? '-';

            $linePayload = [
                "messages" => [
                    [
                        "type" => "flex",
                        "altText" => "เรียน: มีการขอรายงานใหม่ ({$data_name})",
                        "contents" => [
                            "type" => "bubble",
                            "direction" => "ltr",
                            "header" => [
                                "type" => "box",
                                "layout" => "vertical",
                                "backgroundColor" => "#198754",
                                "contents" => [
                                    [
                                        "type" => "text",
                                        "text" => "📢 ขอข้อมูล/รายงานใหม่",
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
                                        "type" => "text",
                                        "text" => $data_name,
                                        "weight" => "bold",
                                        "size" => "md",
                                        "margin" => "md",
                                        "wrap" => true
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
                                                "layout" => "baseline",
                                                "spacing" => "sm",
                                                "contents" => [
                                                    ["type" => "text", "text" => "ผู้ขอ", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                                    ["type" => "text", "text" => $senderName, "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 4]
                                                ]
                                            ],
                                            [
                                                "type" => "box",
                                                "layout" => "baseline",
                                                "spacing" => "sm",
                                                "contents" => [
                                                    ["type" => "text", "text" => "ต้องการข้อมูลภายในวันที่", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                                    ["type" => "text", "text" => $dateText, "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 4]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            $url = "https://morpromt2f.moph.go.th/api/notify/send";
            $headers = [
                "Content-Type: application/json",
                "client-key: {$client_key}",
                "secret-key: {$secret_key}"
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($linePayload, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $lineResponse = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            echo json_encode([
                "status" => "success",
                "message" => "บันทึกข้อมูลสำเร็จ",
                "line_api_response" => $lineResponse,
                "line_api_error" => $curlError,
                "line_api_http_code" => $httpCode
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "ไม่สามารถบันทึกข้อมูลได้"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "ข้อมูลไม่ครบถ้วน"]);
}
