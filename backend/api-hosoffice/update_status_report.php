<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth_utils.php';

function sendMophNotify(string $clientKey, string $secretKey, array $payload): ?array {
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
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $result = curl_exec($ch);
    if($ch !== false) {
        curl_close($ch);
    }
    return json_decode((string)$result, true);
}

// รับข้อมูล JSON
$data = json_decode(file_get_contents("php://input"));

if (
    !empty($data->data_id) &&
    !empty($data->data_status_id)
) {
    $data_id = $data->data_id;
    $data_status_id = $data->data_status_id;

    // Secure Auth
    $userData = authGuard();
    $upd_by = $userData['uid'];

    // Current Time
    $upd_date = date("Y-m-d H:i:s");
    $success_date = ($data_status_id == 3) ? date("Y-m-d H:i:s") : NULL; // ถ้าสถานะคือ ดำเนินการเรียบร้อย (3) ให้ลงวันที่สำเร็จ

    $linked_report_id = isset($data->linked_report_id) && $data->linked_report_id !== '' ? $data->linked_report_id : NULL;

    try {
        if ($success_date) {
            $sql = "UPDATE 10985_data_report SET
                    data_status_id = :data_status_id,
                    success_date = :success_date,
                    `sql` = :sql_text,
                    linked_report_id = :linked_report_id,
                    upd_by = :upd_by,
                    upd_date = :upd_date
                    WHERE data_id = :data_id";
        } else {
            $sql = "UPDATE 10985_data_report SET
                    data_status_id = :data_status_id,
                    `sql` = :sql_text,
                    linked_report_id = :linked_report_id,
                    upd_by = :upd_by,
                    upd_date = :upd_date
                    WHERE data_id = :data_id";
        }

        $stmt = $pdo3->prepare($sql);

        // Bind parameters
        $stmt->bindParam(":data_status_id", $data_status_id);
        if ($success_date) {
            $stmt->bindParam(":success_date", $success_date);
        }
        $stmt->bindParam(":sql_text", $data->sql); // Bind SQL text
        $stmt->bindParam(":linked_report_id", $linked_report_id, PDO::PARAM_INT);
        $stmt->bindParam(":upd_by", $upd_by);
        $stmt->bindParam(":upd_date", $upd_date);
        $stmt->bindParam(":data_id", $data_id);

        if ($stmt->execute()) {
            // --- Send MOPH Notify ---
            try {
                $stmtInfo = $pdo3->prepare("SELECT data_name, crt_by FROM 10985_data_report WHERE data_id = :data_id");
                $stmtInfo->bindParam(":data_id", $data_id);
                $stmtInfo->execute();
                $reportInfo = $stmtInfo->fetch(PDO::FETCH_ASSOC);

                if ($reportInfo && !empty($reportInfo['crt_by'])) {
                    $stmtUser = $pdo3->prepare("SELECT p.MOPH_CLIENT_KEY, p.MOPH_SECRET_KEY, CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as FULLNAME FROM hr_person p LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID WHERE p.id = :id");
                    $stmtUser->bindParam(":id", $reportInfo['crt_by']);
                    $stmtUser->execute();
                    $person = $stmtUser->fetch(PDO::FETCH_ASSOC);

                    if ($person && !empty($person['MOPH_CLIENT_KEY']) && !empty($person['MOPH_SECRET_KEY'])) {
                        $statusMap = [
                            1 => ['name' => 'รอดำเนินการ', 'color' => '#f39c12'],
                            2 => ['name' => 'กำลังดำเนินการ', 'color' => '#3498db'],
                            3 => ['name' => 'ดำเนินการเรียบร้อย', 'color' => '#2ecc71'],
                            4 => ['name' => 'ยกเลิก', 'color' => '#e74c3c']
                        ];
                        $statusInfo = $statusMap[$data_status_id] ?? ['name' => 'อัปเดตสถานะ', 'color' => '#666666'];
                        $statusName = $statusInfo['name'];
                        $statusColor = $statusInfo['color'];

                        $personName = $person['FULLNAME'] ?? 'ผู้ขอข้อมูล';

                        $contents = [
                            [
                                "type" => "box",
                                "layout" => "baseline",
                                "spacing" => "sm",
                                "contents" => [
                                    ["type" => "text", "text" => "เรียน", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                    ["type" => "text", "text" => $personName, "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 4]
                                ]
                            ],
                            [
                                "type" => "box",
                                "layout" => "baseline",
                                "spacing" => "sm",
                                "contents" => [
                                    ["type" => "text", "text" => "เรื่อง", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                    ["type" => "text", "text" => $reportInfo['data_name'], "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 4]
                                ]
                            ],
                            [
                                "type" => "box",
                                "layout" => "baseline",
                                "spacing" => "sm",
                                "contents" => [
                                    ["type" => "text", "text" => "สถานะ", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                    ["type" => "text", "text" => $statusName, "wrap" => true, "color" => $statusColor, "size" => "sm", "weight" => "bold", "flex" => 4]
                                ]
                            ]
                        ];

                        if ($data_status_id == 3 && !empty($success_date)) {
                            $contents[] = [
                                "type" => "box",
                                "layout" => "baseline",
                                "spacing" => "sm",
                                "contents" => [
                                    ["type" => "text", "text" => "วันที่เสร็จ", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                    ["type" => "text", "text" => date("d/m/Y H:i", strtotime($success_date)), "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 4]
                                ]
                            ];
                        }

                        $payload = [
                            'messages' => [
                                [
                                    "type" => "flex",
                                    "altText" => "แจ้งเตือนอัปเดตสถานะขอข้อมูลรายงาน: " . $reportInfo['data_name'],
                                    "contents" => [
                                        "type" => "bubble",
                                        "size" => "mega",
                                        "header" => [
                                            "type" => "box",
                                            "layout" => "vertical",
                                            "backgroundColor" => $statusColor,
                                            "paddingAll" => "20px",
                                            "contents" => [
                                                [
                                                    "type" => "text",
                                                    "text" => "อัปเดตสถานะขอข้อมูล",
                                                    "color" => "#ffffff",
                                                    "weight" => "bold",
                                                    "size" => "xl"
                                                ]
                                            ]
                                        ],
                                        "body" => [
                                            "type" => "box",
                                            "layout" => "vertical",
                                            "spacing" => "md",
                                            "contents" => $contents
                                        ],
                                        "footer" => [
                                            "type" => "box",
                                            "layout" => "vertical",
                                            "contents" => [
                                                [
                                                    "type" => "text",
                                                    "text" => "ระบบแจ้งเตือน Data Center",
                                                    "color" => "#aaaaaa",
                                                    "size" => "xs",
                                                    "align" => "center"
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ];

                        $res = sendMophNotify(trim($person['MOPH_CLIENT_KEY']), trim($person['MOPH_SECRET_KEY']), $payload);
                        error_log("MOPH Notify Result: " . print_r($res, true));
                    } else {
                        error_log("MOPH Notify Error: Missing MOPH_CLIENT_KEY or MOPH_SECRET_KEY for id " . $reportInfo['crt_by']);
                    }
                } else {
                    error_log("MOPH Notify Error: Report info not found or crt_by is empty");
                }
            } catch (Exception $e) {
                // Ignore notification error
                error_log("MOPH Notify Exception: " . $e->getMessage());
            }
            // ------------------------

            echo json_encode(["status" => "success", "message" => "อัปเดตสถานะสำเร็จ"]);
        } else {
            echo json_encode(["status" => "error", "message" => "ไม่สามารถอัปเดตสถานะได้"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "ข้อมูลไม่ครบถ้วน"]);
}
