<?php
/**
 * Daily General Repair MOPH Notify Service
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../moph_notify_helper.php';

date_default_timezone_set('Asia/Bangkok');

function ensureRepairNotifySettingsTable(PDO $pdo2): void
{
    static $ensured = false;
    if ($ensured) return;

    $sql = "CREATE TABLE IF NOT EXISTS `general_repair_notify_settings` (
        `id` INT UNSIGNED NOT NULL PRIMARY KEY,
        `notify_time` VARCHAR(10) NOT NULL DEFAULT '16:00',
        `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
        `channel_code` VARCHAR(64) NOT NULL DEFAULT 'repair',
        `app_base_url` VARCHAR(255) DEFAULT 'http://192.168.7.12:5173',
        `last_sent_date` DATE DEFAULT NULL,
        `last_sent_at` DATETIME DEFAULT NULL,
        `last_status` VARCHAR(32) DEFAULT NULL,
        `last_response` TEXT DEFAULT NULL,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo2->exec($sql);

    try {
        $pdo2->exec("ALTER TABLE general_repair_notify_settings ADD COLUMN app_base_url VARCHAR(255) DEFAULT 'http://192.168.7.12:5173'");
    } catch (Exception $e) {}

    $count = (int)$pdo2->query("SELECT COUNT(*) FROM general_repair_notify_settings WHERE id = 1")->fetchColumn();
    if ($count === 0) {
        $pdo2->exec("INSERT INTO general_repair_notify_settings (id, notify_time, is_enabled, channel_code, app_base_url) VALUES (1, '16:00', 1, 'repair', 'http://192.168.7.12:5173')");
    } else {
        $currUrl = $pdo2->query("SELECT app_base_url FROM general_repair_notify_settings WHERE id = 1")->fetchColumn();
        if (empty($currUrl)) {
            $pdo2->exec("UPDATE general_repair_notify_settings SET app_base_url = 'http://192.168.7.12:5173' WHERE id = 1");
        }
    }

    $ensured = true;
}

function getRepairNotifySettings(PDO $pdo2): array
{
    ensureRepairNotifySettingsTable($pdo2);
    $stmt = $pdo2->query("SELECT * FROM general_repair_notify_settings WHERE id = 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return [
        'notify_time' => $row['notify_time'] ?? '16:00',
        'is_enabled' => (int)($row['is_enabled'] ?? 1),
        'channel_code' => $row['channel_code'] ?? 'repair',
        'app_base_url' => !empty($row['app_base_url']) ? $row['app_base_url'] : 'http://192.168.7.12:5173',
        'last_sent_date' => $row['last_sent_date'] ?? null,
        'last_sent_at' => $row['last_sent_at'] ?? null,
        'last_status' => $row['last_status'] ?? null,
    ];
}

function saveRepairNotifySettings(PDO $pdo2, array $input): array
{
    ensureRepairNotifySettingsTable($pdo2);
    $current = getRepairNotifySettings($pdo2);

    $notifyTime = isset($input['notify_time']) && preg_match('/^\d{2}:\d{2}$/', (string)$input['notify_time'])
        ? (string)$input['notify_time']
        : ($current['notify_time'] ?? '16:00');

    $isEnabled = isset($input['is_enabled']) ? (int)(bool)$input['is_enabled'] : (int)$current['is_enabled'];
    $channelCode = trim((string)($input['channel_code'] ?? $current['channel_code'] ?? 'repair'));
    $appBaseUrl = trim((string)($input['app_base_url'] ?? $current['app_base_url'] ?? 'http://192.168.7.12:5173'));

    // If new future time today, allow re-firing today
    $oldNotifyTime = (string)($current['notify_time'] ?? '');
    $resetSentDate = ($notifyTime !== $oldNotifyTime && $notifyTime > date('H:i'));

    $sql = "UPDATE general_repair_notify_settings
            SET notify_time = :notify_time,
                is_enabled = :is_enabled,
                channel_code = :channel_code,
                app_base_url = :app_base_url" .
                ($resetSentDate ? ", last_sent_date = NULL" : "") . "
            WHERE id = 1";

    $stmt = $pdo2->prepare($sql);
    $stmt->execute([
        ':notify_time' => $notifyTime,
        ':is_enabled' => $isEnabled,
        ':channel_code' => $channelCode ?: 'repair',
        ':app_base_url' => $appBaseUrl ?: 'http://192.168.7.12:5173',
    ]);

    return getRepairNotifySettings($pdo2);
}

function getRepairWebUrl(PDO $pdo2, ?string $ticketNo = null, bool $directToLogin = true): string
{
    $settings = getRepairNotifySettings($pdo2);
    $base = trim((string)($settings['app_base_url'] ?? ''));
    if ($base === '') {
        $base = 'http://192.168.7.12:5173';
    }
    $base = rtrim($base, '/');

    // Target internal path
    $targetPath = '/general-repair';
    if ($ticketNo !== null && $ticketNo !== '') {
        $targetPath .= '?ticket=' . urlencode($ticketNo);
    }

    if ($directToLogin) {
        return "{$base}/login?redirect=" . urlencode($targetPath);
    }

    return "{$base}{$targetPath}";
}

function formatThaiDateLong(string $ymd): string
{
    $months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    $days = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];

    $ts = strtotime($ymd);
    if (!$ts) return $ymd;

    $dayOfWeek = $days[(int)date('w', $ts)];
    $d = (int)date('j', $ts);
    $m = $months[(int)date('n', $ts)] ?? '';
    $y = (int)date('Y', $ts) + 543;

    return "วัน{$dayOfWeek}ที่ {$d} {$m} {$y}";
}

function buildDailyRepairSummaryPayload(PDO $pdo2, string $targetDate): array
{
    // 1. New requests on targetDate
    $stmtNew = $pdo2->prepare("
        SELECT id, ticket_no, repair_title, location_name, requester_name, priority_id, priority_name, status, created_at
        FROM general_repair_requests
        WHERE DATE(created_at) = :dt
        ORDER BY priority_id DESC, id DESC
    ");
    $stmtNew->execute([':dt' => $targetDate]);
    $newRequests = $stmtNew->fetchAll(PDO::FETCH_ASSOC);

    // 2. In progress requests
    $stmtProg = $pdo2->query("
        SELECT id, ticket_no, repair_title, location_name, technician_name, priority_name
        FROM general_repair_requests
        WHERE status = 'In Progress'
        ORDER BY priority_id DESC, id DESC
    ");
    $inProgress = $stmtProg->fetchAll(PDO::FETCH_ASSOC);

    // 3. Wait for parts
    $stmtWait = $pdo2->query("
        SELECT id, ticket_no, repair_title, location_name, technician_name
        FROM general_repair_requests
        WHERE status = 'Wait for Part'
        ORDER BY id DESC
    ");
    $waitParts = $stmtWait->fetchAll(PDO::FETCH_ASSOC);

    // 4. Completed on targetDate
    $stmtComp = $pdo2->prepare("
        SELECT id, ticket_no, repair_title, location_name, technician_name, technician_comment
        FROM general_repair_requests
        WHERE status = 'Completed' AND DATE(completed_at) = :dt
        ORDER BY id DESC
    ");
    $stmtComp->execute([':dt' => $targetDate]);
    $completedToday = $stmtComp->fetchAll(PDO::FETCH_ASSOC);

    // 5. Total pending backlog
    $totalPending = (int)$pdo2->query("SELECT COUNT(*) FROM general_repair_requests WHERE status = 'Pending'")->fetchColumn();
    $totalActive = $totalPending + count($inProgress) + count($waitParts);

    $thaiDateText = formatThaiDateLong($targetDate);

    // Flex Bubble Header
    $headerContents = [
        [
            "type" => "text",
            "text" => "🛠️ สรุปงานซ่อมบำรุงประจำวัน",
            "weight" => "bold",
            "size" => "lg",
            "color" => "#ffffff",
            "align" => "center"
        ],
        [
            "type" => "text",
            "text" => $thaiDateText,
            "size" => "xs",
            "color" => "#ffffffcc",
            "align" => "center",
            "margin" => "xs"
        ]
    ];

    // KPI Counters
    $kpiBoxes = [
        [
            "type" => "box",
            "layout" => "horizontal",
            "spacing" => "sm",
            "contents" => [
                [
                    "type" => "box",
                    "layout" => "vertical",
                    "backgroundColor" => "#fff3cd",
                    "cornerRadius" => "8px",
                    "paddingAll" => "8px",
                    "alignItems" => "center",
                    "contents" => [
                        ["type" => "text", "text" => "แจ้งใหม่", "size" => "xxs", "color" => "#856404"],
                        ["type" => "text", "text" => (string)count($newRequests), "size" => "lg", "weight" => "bold", "color" => "#856404"]
                    ]
                ],
                [
                    "type" => "box",
                    "layout" => "vertical",
                    "backgroundColor" => "#cfe2ff",
                    "cornerRadius" => "8px",
                    "paddingAll" => "8px",
                    "alignItems" => "center",
                    "contents" => [
                        ["type" => "text", "text" => "กำลังซ่อม", "size" => "xxs", "color" => "#084298"],
                        ["type" => "text", "text" => (string)count($inProgress), "size" => "lg", "weight" => "bold", "color" => "#084298"]
                    ]
                ],
                [
                    "type" => "box",
                    "layout" => "vertical",
                    "backgroundColor" => "#d1e7dd",
                    "cornerRadius" => "8px",
                    "paddingAll" => "8px",
                    "alignItems" => "center",
                    "contents" => [
                        ["type" => "text", "text" => "เสร็จวันนี้", "size" => "xxs", "color" => "#0f5132"],
                        ["type" => "text", "text" => (string)count($completedToday), "size" => "lg", "weight" => "bold", "color" => "#0f5132"]
                    ]
                ],
                [
                    "type" => "box",
                    "layout" => "vertical",
                    "backgroundColor" => "#f8d7da",
                    "cornerRadius" => "8px",
                    "paddingAll" => "8px",
                    "alignItems" => "center",
                    "contents" => [
                        ["type" => "text", "text" => "งานค้างสะสม", "size" => "xxs", "color" => "#842029"],
                        ["type" => "text", "text" => (string)$totalActive, "size" => "lg", "weight" => "bold", "color" => "#842029"]
                    ]
                ]
            ]
        ]
    ];

    $bodyContents = array_merge([], $kpiBoxes);

    // List New Requests (max 5 in notification)
    if (!empty($newRequests)) {
        $bodyContents[] = ["type" => "separator", "margin" => "md"];
        $bodyContents[] = [
            "type" => "text",
            "text" => "📌 รายการแจ้งใหม่วันนี้ (" . count($newRequests) . " รายการ)",
            "weight" => "bold",
            "size" => "sm",
            "color" => "#212529",
            "margin" => "md"
        ];

        $sliceNew = array_slice($newRequests, 0, 5);
        foreach ($sliceNew as $nr) {
            $pBadge = ($nr['priority_id'] === '04' || $nr['priority_id'] === '03') ? "🔴 " : "▫️ ";
            $bodyContents[] = [
                "type" => "box",
                "layout" => "vertical",
                "margin" => "xs",
                "contents" => [
                    [
                        "type" => "text",
                        "text" => "{$pBadge}{$nr['ticket_no']}: {$nr['repair_title']}",
                        "size" => "xs",
                        "weight" => "bold",
                        "color" => "#111111",
                        "wrap" => true
                    ],
                    [
                        "type" => "text",
                        "text" => "   สถานที่: " . ($nr['location_name'] ?: '-') . " | ผู้แจ้ง: {$nr['requester_name']}",
                        "size" => "xxs",
                        "color" => "#666666"
                    ]
                ]
            ];
        }

        if (count($newRequests) > 5) {
            $rem = count($newRequests) - 5;
            $bodyContents[] = [
                "type" => "text",
                "text" => "...และอีก {$rem} รายการ",
                "size" => "xxs",
                "color" => "#888888",
                "align" => "center",
                "margin" => "xs"
            ];
        }
    }

    // List In Progress items (max 3)
    if (!empty($inProgress)) {
        $bodyContents[] = ["type" => "separator", "margin" => "md"];
        $bodyContents[] = [
            "type" => "text",
            "text" => "⚙️ งานกำลังดำเนินการ (" . count($inProgress) . " รายการ)",
            "weight" => "bold",
            "size" => "sm",
            "color" => "#0d6efd",
            "margin" => "md"
        ];

        $sliceProg = array_slice($inProgress, 0, 3);
        foreach ($sliceProg as $pr) {
            $tName = $pr['technician_name'] ? " [ช่าง: {$pr['technician_name']}]" : "";
            $bodyContents[] = [
                "type" => "text",
                "text" => "• {$pr['ticket_no']} {$pr['repair_title']}{$tName}",
                "size" => "xs",
                "color" => "#333333",
                "wrap" => true,
                "margin" => "xs"
            ];
        }
    }

    $systemUrl = getRepairWebUrl($pdo2);
    $altSummary = "สรุปงานซ่อมบำรุง ({$targetDate}): แจ้งใหม่ " . count($newRequests) . " รายการ, เสร็จวันนี้ " . count($completedToday) . " รายการ, ค้างสะสม {$totalActive} รายการ คลิกลิงก์: {$systemUrl}";

    $payload = [
        "messages" => [
            [
                "type" => "flex",
                "altText" => $altSummary,
                "contents" => [
                    "type" => "bubble",
                    "direction" => "ltr",
                    "header" => [
                        "type" => "box",
                        "layout" => "vertical",
                        "backgroundColor" => "#0f5132", // Green theme for summary
                        "paddingAll" => "16px",
                        "contents" => $headerContents
                    ],
                    "body" => [
                        "type" => "box",
                        "layout" => "vertical",
                        "paddingAll" => "16px",
                        "contents" => $bodyContents
                    ],
                    "footer" => [
                        "type" => "box",
                        "layout" => "vertical",
                        "spacing" => "sm",
                        "paddingAll" => "16px",
                        "contents" => [
                            [
                                "type" => "button",
                                "style" => "primary",
                                "color" => "#0f5132",
                                "height" => "sm",
                                "action" => [
                                    "type" => "uri",
                                    "label" => "🔐 เข้าสู่ระบบ (งานซ่อมบำรุง)",
                                    "uri" => $systemUrl
                                ]
                            ],
                            [
                                "type" => "text",
                                "text" => $systemUrl,
                                "size" => "xxs",
                                "color" => "#0f5132",
                                "align" => "center",
                                "action" => [
                                    "type" => "uri",
                                    "uri" => $systemUrl
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    return [
        'payload' => $payload,
        'summary' => [
            'target_date' => $targetDate,
            'new_count' => count($newRequests),
            'in_progress_count' => count($inProgress),
            'wait_parts_count' => count($waitParts),
            'completed_today_count' => count($completedToday),
            'total_pending' => $totalPending,
            'total_active' => $totalActive
        ]
    ];
}

function dispatchDailyRepairNotify(PDO $pdo2, array $options = []): array
{
    $settings = getRepairNotifySettings($pdo2);
    $targetDate = !empty($options['target_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$options['target_date'])
        ? (string)$options['target_date']
        : date('Y-m-d');

    $isDryRun = !empty($options['dry_run']);
    $isForce = !empty($options['force']);
    $isAuto = !empty($options['auto']);

    // Check automatic scheduling condition (when running via automated task)
    if ($isAuto && !$isForce && !$isDryRun) {
        if (empty($settings['is_enabled'])) {
            return [
                'status' => 'skipped',
                'message' => 'การส่งสรุปแจ้งเตือนซ่อมบำรุงอัตโนมัติถูกปิดไว้ (disabled)',
                'summary' => null,
            ];
        }

        $scheduledHm = trim((string)($settings['notify_time'] ?? '16:00'));
        $currentHm = date('H:i');

        if ($currentHm < $scheduledHm) {
            return [
                'status' => 'skipped',
                'message' => "ยังไม่ถึงเวลาส่งแจ้งเตือน (ตั้งไว้ {$scheduledHm} น. ขณะนี้ {$currentHm} น.)",
                'summary' => null,
            ];
        }

        if (!empty($settings['last_sent_date']) && $settings['last_sent_date'] === $targetDate) {
            $lastSentTime = !empty($settings['last_sent_at']) ? substr($settings['last_sent_at'], 11, 5) : '';
            // ข้ามเฉพาะเมื่อเคยส่งรอบเวลาที่ตั้งไว้ (>= notify_time) ไปแล้วเท่านั้น
            // ป้องกันกรณีทดสอบกดส่งตอนเช้า (< 16:00) แล้วระบบเข้าใจผิดว่าส่งรอบ 16:00 ไปแล้ว
            if ($lastSentTime !== '' && $lastSentTime >= $scheduledHm) {
                return [
                    'status' => 'skipped',
                    'message' => "ส่งสรุปของวันที่ {$targetDate} เรียบร้อยแล้ว (เมื่อ {$settings['last_sent_at']})",
                    'summary' => null,
                ];
            }
        }
    }

    $built = buildDailyRepairSummaryPayload($pdo2, $targetDate);
    $payload = $built['payload'];
    $summary = $built['summary'];

    if ($isDryRun) {
        return [
            'status' => 'dry_run',
            'message' => 'Dry run completed (no notification sent)',
            'summary' => $summary,
            'payload' => $payload
        ];
    }

    // Channel to send
    $channel = $settings['channel_code'] ?: 'repair';

    // Send to channel (with fallback to 'admin')
    $res = sendMophNotifyToChannel($pdo2, $channel, $payload);
    if (empty($res['ok'])) {
        $res = sendMophNotifyToChannel($pdo2, 'admin', $payload);
    }

    $isSuccess = !empty($res['ok']);

    // Record last sent if successful or auto mode
    if ($isSuccess) {
        $upd = $pdo2->prepare("
            UPDATE general_repair_notify_settings 
            SET last_sent_date = :dt,
                last_sent_at = NOW(),
                last_status = :status,
                last_response = :res
            WHERE id = 1
        ");
        $upd->execute([
            ':dt' => $targetDate,
            ':status' => 'success',
            ':res' => json_encode($res, JSON_UNESCAPED_UNICODE)
        ]);
    }

    return [
        'status' => $isSuccess ? 'success' : 'error',
        'message' => $isSuccess ? 'Notification dispatched successfully' : ($res['curl_error'] ?? 'Send failed'),
        'summary' => $summary,
        'notify_response' => $res,
        'payload' => $payload
    ];
}
