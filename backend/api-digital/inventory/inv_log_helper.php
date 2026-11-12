<?php
/**
 * Unified Inventory Audit & Page Activity Log Helper
 *
 * Stores all page visits, warehouse switches, document actions, exports,
 * and master-data changes for the Unified Inventory System in `digital.inv_activity_logs`.
 */

require_once __DIR__ . '/../../auth_utils.php';

function ensureInvActivityLogsTable(PDO $pdo2): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $sql = "CREATE TABLE IF NOT EXISTS `inv_activity_logs` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `warehouse_id` INT DEFAULT NULL,
        `warehouse_name` VARCHAR(150) DEFAULT NULL,
        `user_id` INT DEFAULT NULL,
        `username` VARCHAR(100) DEFAULT NULL,
        `user_fullname` VARCHAR(180) DEFAULT NULL,
        `page_code` VARCHAR(64) NOT NULL DEFAULT 'inventory',
        `page_name` VARCHAR(150) DEFAULT NULL,
        `action` VARCHAR(64) NOT NULL,
        `target_type` VARCHAR(64) DEFAULT NULL,
        `target_id` VARCHAR(64) DEFAULT NULL,
        `details` TEXT DEFAULT NULL,
        `meta_json` MEDIUMTEXT DEFAULT NULL,
        `ip_address` VARCHAR(64) DEFAULT NULL,
        `user_agent` VARCHAR(255) DEFAULT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_inv_logs_wh_time` (`warehouse_id`, `created_at`),
        INDEX `idx_inv_logs_page_time` (`page_code`, `created_at`),
        INDEX `idx_inv_logs_action_time` (`action`, `created_at`),
        INDEX `idx_inv_logs_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo2->exec($sql);
    $ensured = true;
}

function getInvRequestClientIp(): string
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($parts[0]);
        if ($ip !== '') {
            return mb_substr($ip, 0, 64, 'UTF-8');
        }
    }
    return mb_substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64, 'UTF-8');
}

function resolveInvLogActor(?PDO $pdo3 = null, ?string $fallbackName = null, ?int $fallbackUserId = null): array
{
    static $cached = null;
    if ($cached !== null && $fallbackName === null && $fallbackUserId === null) {
        return $cached;
    }

    $tokenData = (function_exists('authOptional') && function_exists('getallheaders')) ? authOptional() : null;
    $uid = (int)($tokenData['uid'] ?? $fallbackUserId ?? 0);
    $username = trim((string)($tokenData['user'] ?? ''));
    $fullname = trim((string)($fallbackName ?? ''));

    if ($pdo3 instanceof PDO && $uid > 0) {
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
                if (!empty($row['fullname'])) {
                    $fullname = trim((string)$row['fullname']);
                }
            }
        } catch (Throwable $e) {
            // Keep JWT/fallback actor info if hr_person lookup fails
        }
    }

    if ($fullname === '') {
        $fullname = $username !== '' ? $username : 'System';
    }

    $actor = [
        'user_id' => $uid > 0 ? $uid : null,
        'username' => $username !== '' ? $username : null,
        'user_fullname' => mb_substr($fullname, 0, 180, 'UTF-8'),
    ];

    if ($fallbackName === null && $fallbackUserId === null) {
        $cached = $actor;
    }
    return $actor;
}

function resolveInvWarehouseLabel(PDO $pdo2, ?int $warehouseId): ?string
{
    static $whCache = [];
    if (!$warehouseId || $warehouseId <= 0) {
        return 'ทุกคลัง (All Warehouses)';
    }
    if (isset($whCache[$warehouseId])) {
        return $whCache[$warehouseId];
    }

    try {
        $st = $pdo2->prepare("SELECT code, name FROM inv_warehouses WHERE id = ? LIMIT 1");
        $st->execute([$warehouseId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $label = "[{$row['code']}] {$row['name']}";
            $whCache[$warehouseId] = $label;
            return $label;
        }
    } catch (Throwable $e) {
        // Ignore lookup failures in log helper
    }
    return "คลัง #{$warehouseId}";
}

function recordInvActivityLog(PDO $pdo2, array $entry, ?PDO $pdo3 = null): void
{
    try {
        ensureInvActivityLogsTable($pdo2);

        $whId = isset($entry['warehouse_id']) && (int)$entry['warehouse_id'] > 0
            ? (int)$entry['warehouse_id']
            : null;
        $whName = isset($entry['warehouse_name']) && trim((string)$entry['warehouse_name']) !== ''
            ? trim((string)$entry['warehouse_name'])
            : resolveInvWarehouseLabel($pdo2, $whId);

        $actor = resolveInvLogActor(
            $pdo3,
            isset($entry['user_fullname']) ? (string)$entry['user_fullname'] : (isset($entry['user_name']) ? (string)$entry['user_name'] : null),
            isset($entry['user_id']) ? (int)$entry['user_id'] : null
        );

        $meta = $entry['meta'] ?? null;
        $metaJson = null;
        if (is_array($meta) && !empty($meta)) {
            $metaJson = json_encode($meta, JSON_UNESCAPED_UNICODE);
        } elseif (is_string($meta) && trim($meta) !== '') {
            $metaJson = $meta;
        }

        $ua = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255, 'UTF-8');

        $st = $pdo2->prepare("
            INSERT INTO inv_activity_logs
                (warehouse_id, warehouse_name, user_id, username, user_fullname, page_code, page_name, action, target_type, target_id, details, meta_json, ip_address, user_agent)
            VALUES
                (:wh_id, :wh_name, :uid, :uname, :fullname, :page_code, :page_name, :action, :target_type, :target_id, :details, :meta_json, :ip, :ua)
        ");
        $st->execute([
            ':wh_id' => $whId,
            ':wh_name' => $whName ? mb_substr($whName, 0, 150, 'UTF-8') : null,
            ':uid' => $actor['user_id'],
            ':uname' => $actor['username'],
            ':fullname' => $actor['user_fullname'],
            ':page_code' => mb_substr((string)($entry['page_code'] ?? 'inventory'), 0, 64, 'UTF-8'),
            ':page_name' => isset($entry['page_name']) ? mb_substr((string)$entry['page_name'], 0, 150, 'UTF-8') : null,
            ':action' => mb_substr((string)($entry['action'] ?? 'ACTION'), 0, 64, 'UTF-8'),
            ':target_type' => isset($entry['target_type']) ? mb_substr((string)$entry['target_type'], 0, 64, 'UTF-8') : null,
            ':target_id' => isset($entry['target_id']) ? mb_substr((string)$entry['target_id'], 0, 64, 'UTF-8') : null,
            ':details' => isset($entry['details']) ? (string)$entry['details'] : null,
            ':meta_json' => $metaJson,
            ':ip' => getInvRequestClientIp(),
            ':ua' => $ua !== '' ? $ua : null,
        ]);
    } catch (Throwable $e) {
        // Logging must never crash primary inventory operations
        error_log('inv_activity_logs error: ' . $e->getMessage());
    }
}
