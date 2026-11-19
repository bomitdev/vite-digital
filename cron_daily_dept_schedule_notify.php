<?php
/**
 * Daily Department Schedule MOPH Notify CLI Runner
 *
 * Checks dept_schedule_groups with notify_enabled = 1 and dispatches
 * today's on-call staff list to the configured MOPH Notify channel
 * on matching weekdays (1=Mon..7=Sun) and time (HH:MM).
 *
 * Usage:
 *   php cron_daily_dept_schedule_notify.php --auto
 *   php cron_daily_dept_schedule_notify.php --force
 */

require_once __DIR__ . '/backend/config.php';
require_once __DIR__ . '/backend/moph_notify_helper.php';
require_once __DIR__ . '/backend/api-digital/dept_schedule/notify_helper.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'CLI access only']);
    exit(1);
}

$force = in_array('--force', $argv, true);
$todayDate = date('Y-m-d');
$todayWeekday = (int)date('N'); // 1 (Mon) .. 7 (Sun)
$nowTime = date('H:i');
$y = (int)date('Y');
$m = (int)date('n');
$d = (int)date('j');

$thaiMonths = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
$dateLabel = $d . ' ' . $thaiMonths[$m] . ' ' . ($y + 543);

try {
    $chkTable = $pdo2->query("SHOW TABLES LIKE 'dept_schedule_groups'")->fetchColumn();
    if (!$chkTable) {
        exit(0);
    }

    $stmt = $pdo2->query("
        SELECT * FROM dept_schedule_groups
        WHERE active = 1 AND notify_enabled = 1 AND notify_channel IS NOT NULL AND notify_channel != ''
    ");
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($groups as $group) {
        if (!$force) {
            if (($group['last_notified_date'] ?? '') === $todayDate) {
                continue;
            }
            $days = array_map('intval', array_filter(explode(',', (string)($group['notify_days'] ?? '1,2,3,4,5,6,7'))));
            if (!in_array($todayWeekday, $days, true)) {
                continue;
            }
            $targetTime = substr((string)($group['notify_time'] ?? '08:00'), 0, 5);
            if ($nowTime < $targetTime) {
                continue;
            }
        }

        $payload = buildDeptScheduleFlexPayload($pdo2, $group, $todayDate, false);
        if (!$payload) {
            $pdo2->prepare("UPDATE dept_schedule_groups SET last_notified_date = ? WHERE id = ?")->execute([$todayDate, $group['id']]);
            continue;
        }

        $res = sendMophNotifyToChannel($pdo2, $group['notify_channel'], $payload);

        $pdo2->prepare("UPDATE dept_schedule_groups SET last_notified_date = ? WHERE id = ?")->execute([$todayDate, $group['id']]);

        $statusTxt = !empty($res['ok']) ? 'SENT' : 'ERROR';
        $logLine = "[" . date('Y-m-d H:i:s') . "] dept_schedule group={$group['id']} ({$group['title']}) status={$statusTxt}";
        echo $logLine . "\n";
        $logDir = __DIR__ . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0777, true);
        @file_put_contents($logDir . DIRECTORY_SEPARATOR . 'daily-notify.log', $logLine . PHP_EOL, FILE_APPEND);
    }
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

