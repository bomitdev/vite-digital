<?php
/**
 * Central Audit Log Helper (90-Day Legal Retention Support)
 *
 * Stores authentication, authorization, data-mutation, and export events
 * in `sys_audit_logs` (append-only) and supports archiving & purging rows
 * older than the configured retention period (default: 90 days).
 */

require_once __DIR__ . '/auth_utils.php';

function ensureSysAuditLogsTable(PDO $pdo2): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $sql = "CREATE TABLE IF NOT EXISTS `sys_audit_logs` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT DEFAULT NULL,
        `username` VARCHAR(100) DEFAULT NULL,
        `fullname` VARCHAR(180) DEFAULT NULL,
        `module` VARCHAR(64) NOT NULL DEFAULT 'system',
        `action` VARCHAR(64) NOT NULL,
        `method` VARCHAR(16) DEFAULT NULL,
        `endpoint` VARCHAR(255) DEFAULT NULL,
        `target_type` VARCHAR(64) DEFAULT NULL,
        `target_id` VARCHAR(64) DEFAULT NULL,
        `status_code` SMALLINT DEFAULT NULL,
        `details` TEXT DEFAULT NULL,
        `meta_json` MEDIUMTEXT DEFAULT NULL,
        `ip_address` VARCHAR(64) DEFAULT NULL,
        `user_agent` VARCHAR(255) DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_audit_created` (`created_at`),
        INDEX `idx_audit_user_time` (`user_id`, `created_at`),
        INDEX `idx_audit_module_time` (`module`, `created_at`),
        INDEX `idx_audit_action_time` (`action`, `created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo2->exec($sql);
    $ensured = true;
}

function getAuditClientIp(): string
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($parts[0]);
        if ($ip !== '') {
            return mb_substr($ip, 0, 64, 'UTF-8');
        }
    }
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        return mb_substr(trim((string)$_SERVER['HTTP_X_REAL_IP']), 0, 64, 'UTF-8');
    }
    return mb_substr((string)($_SERVER['REMOTE_ADDR'] ?? 'CLI'), 0, 64, 'UTF-8');
}

function resolveAuditActor(?PDO $pdo3 = null, array $overrides = []): array
{
    $uid = isset($overrides['user_id']) && (int)$overrides['user_id'] > 0 ? (int)$overrides['user_id'] : 0;
    $username = trim((string)($overrides['username'] ?? ''));
    $fullname = trim((string)($overrides['fullname'] ?? ''));

    if ($uid <= 0 && function_exists('authOptional')) {
        $tokenUser = authOptional();
        if (is_array($tokenUser)) {
            $uid = (int)($tokenUser['uid'] ?? 0);
            if ($username === '' && !empty($tokenUser['user'])) {
                $username = trim((string)$tokenUser['user']);
            }
            if ($fullname === '' && !empty($tokenUser['name'])) {
                $fullname = trim((string)$tokenUser['name']);
            }
        }
    }

    if ($uid <= 0 && session_status() === PHP_SESSION_ACTIVE) {
        $uid = (int)($_SESSION['user_id'] ?? $_SESSION['uid'] ?? 0);
        if ($username === '' && !empty($_SESSION['username'])) {
            $username = trim((string)$_SESSION['username']);
        }
        if ($fullname === '' && !empty($_SESSION['officer_name'])) {
            $fullname = trim((string)$_SESSION['officer_name']);
        }
    }

    if ($pdo3 instanceof PDO && $uid > 0 && ($username === '' || $fullname === '')) {
        try {
            $st = $pdo3->prepare("
                SELECT p.HR_USERNAME,
                       TRIM(CONCAT(COALESCE(pf.HR_PREFIX_NAME, ''), p.HR_FNAME, ' ', p.HR_LNAME)) AS fullname
                FROM hr_person p
                LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
                WHERE p.ID = :id
                LIMIT 1
            ");
            $st->execute([':id' => $uid]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                if ($username === '' && !empty($row['HR_USERNAME'])) {
                    $username = trim((string)$row['HR_USERNAME']);
                }
                if ($fullname === '' && !empty($row['fullname'])) {
                    $fullname = trim((string)$row['fullname']);
                }
            }
        } catch (Throwable $e) {
            // Keep available actor fields if lookup fails
        }
    }

    return [
        'user_id' => $uid > 0 ? $uid : null,
        'username' => $username !== '' ? mb_substr($username, 0, 100, 'UTF-8') : null,
        'fullname' => $fullname !== '' ? mb_substr($fullname, 0, 180, 'UTF-8') : null,
    ];
}

function sanitizeAuditMeta($meta)
{
    if (!is_array($meta)) {
        return $meta;
    }
    $sensitiveKeys = ['password', 'pass', 'pwd', 'token', 'temp_token', 'authorization', 'secret', 'otp'];
    $clean = [];
    foreach ($meta as $k => $v) {
        $lk = strtolower((string)$k);
        $isSensitive = false;
        foreach ($sensitiveKeys as $sk) {
            if (strpos($lk, $sk) !== false) {
                $isSensitive = true;
                break;
            }
        }
        if ($isSensitive) {
            $clean[$k] = '[REDACTED]';
        } elseif (is_array($v)) {
            $clean[$k] = sanitizeAuditMeta($v);
        } else {
            $clean[$k] = $v;
        }
    }
    return $clean;
}

function recordAuditLog(PDO $pdo2, array $entry, ?PDO $pdo3 = null): void
{
    try {
        ensureSysAuditLogsTable($pdo2);

        $actor = resolveAuditActor($pdo3, [
            'user_id' => $entry['user_id'] ?? null,
            'username' => $entry['username'] ?? null,
            'fullname' => $entry['fullname'] ?? ($entry['user_fullname'] ?? null),
        ]);

        $meta = $entry['meta'] ?? null;
        $metaJson = null;
        if (is_array($meta) && !empty($meta)) {
            $metaJson = json_encode(sanitizeAuditMeta($meta), JSON_UNESCAPED_UNICODE);
        } elseif (is_string($meta) && trim($meta) !== '') {
            $metaJson = $meta;
        }

        $method = $entry['method'] ?? ($_SERVER['REQUEST_METHOD'] ?? 'CLI');
        $endpoint = $entry['endpoint'] ?? ($_SERVER['REQUEST_URI'] ?? ($_SERVER['SCRIPT_NAME'] ?? ''));
        $endpoint = strtok((string)$endpoint, '?');
        $ua = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255, 'UTF-8');

        $st = $pdo2->prepare("
            INSERT INTO sys_audit_logs
                (user_id, username, fullname, module, action, method, endpoint, target_type, target_id, status_code, details, meta_json, ip_address, user_agent)
            VALUES
                (:uid, :uname, :fullname, :module, :action, :method, :endpoint, :target_type, :target_id, :status_code, :details, :meta_json, :ip, :ua)
        ");
        $st->execute([
            ':uid' => $actor['user_id'],
            ':uname' => $actor['username'],
            ':fullname' => $actor['fullname'],
            ':module' => mb_substr((string)($entry['module'] ?? 'system'), 0, 64, 'UTF-8'),
            ':action' => mb_substr((string)($entry['action'] ?? 'ACTION'), 0, 64, 'UTF-8'),
            ':method' => mb_substr((string)$method, 0, 16, 'UTF-8'),
            ':endpoint' => $endpoint !== '' ? mb_substr((string)$endpoint, 0, 255, 'UTF-8') : null,
            ':target_type' => isset($entry['target_type']) ? mb_substr((string)$entry['target_type'], 0, 64, 'UTF-8') : null,
            ':target_id' => isset($entry['target_id']) ? mb_substr((string)$entry['target_id'], 0, 64, 'UTF-8') : null,
            ':status_code' => isset($entry['status_code']) ? (int)$entry['status_code'] : null,
            ':details' => isset($entry['details']) ? (string)$entry['details'] : null,
            ':meta_json' => $metaJson,
            ':ip' => getAuditClientIp(),
            ':ua' => $ua !== '' ? $ua : null,
        ]);
    } catch (Throwable $e) {
        error_log('sys_audit_logs error: ' . $e->getMessage());
    }
}

/**
 * Archive rows older than $retentionDays to JSONL(.gz) files and purge from DB.
 */
function archiveAndPurgeAuditLogs(PDO $pdo2, int $retentionDays = 90, ?string $archiveDir = null): array
{
    ensureSysAuditLogsTable($pdo2);

    $retentionDays = max(90, $retentionDays);
    $cutoff = date('Y-m-d H:i:s', strtotime("-{$retentionDays} days"));

    if ($archiveDir === null) {
        $archiveDir = __DIR__ . '/logs/archive';
    }
    if (!is_dir($archiveDir)) {
        @mkdir($archiveDir, 0755, true);
    }

    $stCount = $pdo2->prepare("SELECT COUNT(*) AS cnt, MAX(id) AS max_id FROM sys_audit_logs WHERE created_at < ?");
    $stCount->execute([$cutoff]);
    $info = $stCount->fetch(PDO::FETCH_ASSOC);
    $totalOld = (int)($info['cnt'] ?? 0);
    $maxId = (int)($info['max_id'] ?? 0);

    if ($totalOld === 0 || $maxId <= 0) {
        return [
            'status' => 'success',
            'retention_days' => $retentionDays,
            'cutoff' => $cutoff,
            'archived_count' => 0,
            'purged_count' => 0,
            'archive_file' => null,
        ];
    }

    $stamp = date('Ymd_His');
    $useGzip = function_exists('gzopen');
    $archiveFile = $archiveDir . DIRECTORY_SEPARATOR . "sys_audit_logs_before_{$stamp}." . ($useGzip ? 'jsonl.gz' : 'jsonl');

    $handle = $useGzip ? @gzopen($archiveFile, 'wb9') : @fopen($archiveFile, 'wb');
    if (!$handle) {
        throw new RuntimeException("Cannot open archive file for writing: {$archiveFile}");
    }

    $archivedCount = 0;
    $batchSize = 1000;
    $lastId = 0;

    try {
        while (true) {
            $st = $pdo2->prepare("
                SELECT * FROM sys_audit_logs
                WHERE id > ? AND id <= ? AND created_at < ?
                ORDER BY id ASC
                LIMIT {$batchSize}
            ");
            $st->execute([$lastId, $maxId, $cutoff]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC);
            if (empty($rows)) {
                break;
            }
            foreach ($rows as $row) {
                $line = json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
                if ($useGzip) {
                    gzwrite($handle, $line);
                } else {
                    fwrite($handle, $line);
                }
                $lastId = (int)$row['id'];
                $archivedCount++;
            }
        }
    } finally {
        if ($useGzip) {
            gzclose($handle);
        } else {
            fclose($handle);
        }
    }

    $stDel = $pdo2->prepare("DELETE FROM sys_audit_logs WHERE id <= ? AND created_at < ?");
    $stDel->execute([$maxId, $cutoff]);
    $purgedCount = $stDel->rowCount();

    return [
        'status' => 'success',
        'retention_days' => $retentionDays,
        'cutoff' => $cutoff,
        'archived_count' => $archivedCount,
        'purged_count' => $purgedCount,
        'archive_file' => $archiveFile,
    ];
}

