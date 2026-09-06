<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../../config.php';

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
    return json_decode((string)$result, true);
}

$data = json_decode(file_get_contents("php://input"));
$kpi_id = $data->kpi_id ?? '';

if (empty($kpi_id)) {
    echo json_encode(['status' => 'error', 'message' => 'KPI ID is required']);
    exit;
}

try {
    // 1. Get KPI details
    $stmt = $pdo2->prepare("SELECT * FROM kpi_definitions WHERE id = :id");
    $stmt->execute([':id' => $kpi_id]);
    $kpi = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$kpi) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลตัวชี้วัด']);
        exit;
    }

    $responsible_person = $kpi['responsible_person'];
    
    if (empty($responsible_person)) {
        echo json_encode(['status' => 'error', 'message' => 'ยังไม่มีการระบุชื่อผู้รับผิดชอบตัวชี้วัดนี้']);
        exit;
    }

    // Process names
    $persons = array_map('trim', explode(',', $responsible_person));
    
    $success_count = 0;
    $not_found_tokens = [];
    $sent_names = [];

    foreach ($persons as $person_name) {
        if (empty($person_name)) continue;

        // Remove spaces for robust matching
        $clean_name = str_replace(' ', '', $person_name);
        
        // Handle specific known typo for Sarawut
        $clean_name = str_replace('สราวุฒิ', 'ศราวุฒิ', $clean_name);

        $sql = "SELECT p.MOPH_CLIENT_KEY, p.MOPH_SECRET_KEY, CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as FULLNAME 
                FROM hr_person p
                LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
                WHERE REPLACE(CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, p.HR_LNAME), ' ', '') LIKE :name1 
                OR REPLACE(CONCAT(p.HR_FNAME, p.HR_LNAME), ' ', '') LIKE :name2
                LIMIT 1";
        
        $stmt_person = $pdo3->prepare($sql);
        $stmt_person->execute([
            ':name1' => '%' . $clean_name . '%',
            ':name2' => '%' . $clean_name . '%'
        ]);
        $hr_person = $stmt_person->fetch(PDO::FETCH_ASSOC);

        $clientKey = $hr_person ? trim($hr_person['MOPH_CLIENT_KEY']) : '';
        $secretKey = $hr_person ? trim($hr_person['MOPH_SECRET_KEY']) : '';

        if (!empty($clientKey) && !empty($secretKey)) {
            
            $kpiCode = $kpi['code'] ? $kpi['code'] : '-';
            $kpiName = $kpi['name'];
            
            $payload = [
                "messages" => [
                    [
                        "type" => "flex",
                        "altText" => "แจ้งเตือนรายงานผล KPI: $kpiCode",
                        "contents" => [
                            "type" => "bubble",
                            "direction" => "ltr",
                            "header" => [
                                "type" => "box",
                                "layout" => "vertical",
                                "backgroundColor" => "#1a3e6f",
                                "contents" => [
                                    [
                                        "type" => "text",
                                        "text" => "🔔 แจ้งเตือนส่งผลตัวชี้วัด",
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
                                        "text" => $kpiCode,
                                        "weight" => "bold",
                                        "size" => "xl",
                                        "color" => "#1a3e6f",
                                        "align" => "center",
                                        "margin" => "md"
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
                                                    ["type" => "text", "text" => "เรียน", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                                    ["type" => "text", "text" => $person_name, "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 4]
                                                ]
                                            ],
                                            [
                                                "type" => "box",
                                                "layout" => "baseline",
                                                "spacing" => "sm",
                                                "contents" => [
                                                    ["type" => "text", "text" => "ตัวชี้วัด", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                                    ["type" => "text", "text" => $kpiName, "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 4]
                                                ]
                                            ],
                                            [
                                                "type" => "box",
                                                "layout" => "baseline",
                                                "spacing" => "sm",
                                                "contents" => [
                                                    ["type" => "text", "text" => "เรื่อง", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                                    ["type" => "text", "text" => "กรุณารายงานผลการดำเนินงาน", "wrap" => true, "color" => "#28a745", "size" => "sm", "weight" => "bold", "flex" => 4]
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
            
            $res = sendMophNotify($clientKey, $secretKey, $payload);
            
            // Check MOPH API response, usually 200 OK or it returns a message
            if (isset($res['status']) && ($res['status'] == 200 || $res['status'] == 'success' || strtolower($res['status']) == 'ok')) {
                $success_count++;
                $sent_names[] = $person_name;
            } else {
                $not_found_tokens[] = $person_name . " (API แจ้งข้อผิดพลาด)";
            }
        } else {
            // Token is empty or person not found
            $not_found_tokens[] = $person_name . " (ไม่พบข้อมูล MOPH Key)";
        }
    }

    if ($success_count > 0) {
        echo json_encode([
            'status' => 'success', 
            'message' => 'ส่งการแจ้งเตือนสำเร็จจำนวน ' . $success_count . ' รายการ',
            'sent_to' => implode(', ', $sent_names),
            'missing_tokens' => implode(', ', $not_found_tokens)
        ]);
    } else {
        echo json_encode([
            'status' => 'error', 
            'message' => 'ไม่พบข้อมูล MOPH Notify Key ของผู้รับผิดชอบ หรือการส่งล้มเหลว',
            'missing_tokens' => implode(', ', $not_found_tokens)
        ]);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
