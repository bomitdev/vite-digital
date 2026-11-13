<?php
/**
 * Daily Schedule MOPH Notify CLI Runner
 *
 * Sends LINE notification via MOPH Notify with:
 * 1. OPD Card duties for today and tomorrow
 * 2. Staff on leave today
 * 3. Staff on official trips today
 *
 * Usage:
 *   php cron_daily_schedule_notify.php --auto
 *   php cron_daily_schedule_notify.php --force
 *   php cron_daily_schedule_notify.php --dry-run
 *   php cron_daily_schedule_notify.php --dry-run --date=2026-11-12
 */

require_once __DIR__ . '/backend/config.php';
require_once __DIR__ . '/backend/api-digital/schedule/daily_notify_service.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'CLI access only']);
    exit(1);
}

$options = [
    'auto' => in_array('--auto', $argv, true),
    'force' => in_array('--force', $argv, true),
    'dry_run' => in_array('--dry-run', $argv, true),
    'verbose' => in_array('--verbose', $argv, true),
    'target_date' => date('Y-m-d'),
];

foreach ($argv as $arg) {
    if (strpos($arg, '--date=') === 0) {
        $candidate = substr($arg, 7);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $candidate)) {
            $options['target_date'] = $candidate;
        }
    }
}

function appendDailyNotifyLog(string $line): void
{
    $logDir = __DIR__ . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }
    $logFile = $logDir . DIRECTORY_SEPARATOR . 'daily-notify.log';
    @file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND);
}

try {
    $result = dispatchDailyScheduleNotify($pdo2, $pdo3, $options);
    $now = date('Y-m-d H:i:s');
    $shouldPrint = !$options['auto'] || $options['verbose'] || ($result['status'] !== 'skipped');

    if ($shouldPrint) {
        $line = "[{$now}] status={$result['status']} date={$options['target_date']} msg={$result['message']}";
        echo $line . "\n";

        if ($options['auto'] && $result['status'] !== 'skipped') {
            appendDailyNotifyLog($line);
        }

        if (!empty($result['summary'])) {
            $s = $result['summary'];
            echo "OPD Today    : " . count($s['opd_today']) . " person(s)\n";
            echo "OPD Tomorrow : " . count($s['opd_tomorrow']) . " person(s)\n";
            echo "Leaves Today : " . count($s['leaves_today']) . " person(s)\n";
            echo "Trips Today  : " . count($s['trips_today']) . " item(s)\n";
        }
    }

    if (!empty($options['dry_run']) && !empty($result['payload'])) {
        echo "\n=== DRY RUN FLEX PAYLOAD ===\n";
        echo json_encode($result['payload'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    }

    exit($result['status'] === 'error' ? 1 : 0);
} catch (Throwable $e) {
    $errLine = '[' . date('Y-m-d H:i:s') . '] Fatal error: ' . $e->getMessage();
    appendDailyNotifyLog($errLine);
    fwrite(STDERR, $errLine . "\n");
    exit(1);
}
