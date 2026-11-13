<?php
/**
 * Daily General Repair MOPH Notify CLI Runner
 *
 * Sends LINE notification via MOPH Notify with daily maintenance summary:
 * - New repair requests today
 * - Repairs currently in progress
 * - Repairs waiting for parts
 * - Repairs completed today
 * - Total active maintenance backlog
 *
 * Usage:
 *   php cron_daily_repair_notify.php --auto
 *   php cron_daily_repair_notify.php --force
 *   php cron_daily_repair_notify.php --dry-run
 *   php cron_daily_repair_notify.php --dry-run --date=2026-11-12
 */

require_once __DIR__ . '/backend/config.php';
require_once __DIR__ . '/backend/api-digital/general_repair/daily_repair_notify_service.php';

date_default_timezone_set('Asia/Bangkok');

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

function appendRepairDailyLog(string $line): void
{
    $logDir = __DIR__ . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }
    $logFile = $logDir . DIRECTORY_SEPARATOR . 'daily-repair-notify.log';
    @file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND);
}

try {
    $result = dispatchDailyRepairNotify($pdo2, $options);
    $now = date('Y-m-d H:i:s');
    $shouldPrint = !$options['auto'] || $options['verbose'] || ($result['status'] !== 'skipped');

    if ($shouldPrint) {
        $line = "[{$now}] status={$result['status']} date={$options['target_date']} msg={$result['message']}";
        echo $line . "\n";

        if ($options['auto'] && $result['status'] !== 'skipped') {
            appendRepairDailyLog($line);
        }

        if (!empty($result['summary'])) {
            $s = $result['summary'];
            echo "New Requests Today : " . $s['new_count'] . "\n";
            echo "In Progress        : " . $s['in_progress_count'] . "\n";
            echo "Waiting for Parts  : " . $s['wait_parts_count'] . "\n";
            echo "Completed Today    : " . $s['completed_today_count'] . "\n";
            echo "Total Active Backlog: " . $s['total_active'] . "\n";
        }
    }

    if (!empty($options['dry_run']) && !empty($result['payload'])) {
        echo "\n=== DRY RUN FLEX PAYLOAD ===\n";
        echo json_encode($result['payload'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    }

    exit($result['status'] === 'error' ? 1 : 0);
} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    exit(1);
}

