<?php
/**
 * Daily Cron: Archive & Purge Central Audit Logs older than 90 days
 * Usage: php cron_audit_log_retention.php
 */

require_once __DIR__ . '/backend/config.php';
require_once __DIR__ . '/backend/audit_log_helper.php';

if (!isset($pdo2)) {
    fwrite(STDERR, "Database connection \$pdo2 missing.\n");
    exit(1);
}

try {
    $result = archiveAndPurgeAuditLogs($pdo2, 90);
    recordAuditLog($pdo2, [
        'module' => 'system',
        'action' => 'LOG_RETENTION_RUN',
        'method' => 'CLI',
        'endpoint' => 'cron_audit_log_retention.php',
        'status_code' => 200,
        'details' => "Archived {$result['archived_count']} and purged {$result['purged_count']} audit log(s) older than 90 days.",
        'meta' => $result,
    ], $pdo3 ?? null);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Retention error: " . $e->getMessage() . "\n");
    exit(1);
}

