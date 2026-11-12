<?php
/**
 * Shared MOPH Notify Helper & DB Channel Repository
 *
 * Stores MOPH Notify channel credentials centrally in the `digital` database
 * (`moph_notify_channels`) so API keys stay out of source code / GitHub.
 */

const DEFAULT_MOPH_NOTIFY_URL = 'https://morpromt2f.moph.go.th/api/notify/send';

/**
 * Resolve user's access permissions and sub-department from hosoffice hr_person ($pdo3).
 */
function getUserAccessProfile(PDO $pdo3, int $userId): array
{
    if ($userId <= 0) {
        return ['perms' => [], 'dept_name' => '', 'is_admin' => false];
    }

    $stmt = $pdo3->prepare("
        SELECT p.access_user, COALESCE(hds.HR_DEPARTMENT_SUB_NAME, '') AS dept_name
        FROM hr_person p
        LEFT JOIN hr_department_sub hds ON p.HR_DEPARTMENT_SUB_ID = hds.HR_DEPARTMENT_SUB_ID
        WHERE p.ID = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $perms = $row && !empty($row['access_user'])
        ? array_values(array_filter(array_map('trim', explode(':', (string)$row['access_user']))))
        : [];
    $deptName = (string)($row['dept_name'] ?? '');

    return [
        'perms' => $perms,
        'dept_name' => $deptName,
        'is_admin' => in_array('administrator', $perms, true),
    ];
}

/**
 * Env key mapping for initial seed / fallback of built-in system channels.
 */
function getMophEnvFallback(string $channelCode): array
{
    $url = trim((string)($_ENV['MOPH_NOTIFY_URL'] ?? getenv('MOPH_NOTIFY_URL') ?: '')) ?: DEFAULT_MOPH_NOTIFY_URL;
    $map = [
        'it' => ['MOPH_IT_CLIENT_KEY', 'MOPH_IT_SECRET_KEY'],
        'admin' => ['MOPH_ADMIN_CLIENT_KEY', 'MOPH_ADMIN_SECRET_KEY'],
        'opdcard' => ['MOPH_OPDCARD_CLIENT_KEY', 'MOPH_OPDCARD_SECRET_KEY'],
    ];

    if (!isset($map[$channelCode])) {
        return ['url' => $url, 'client_key' => '', 'secret_key' => ''];
    }

    [$ckEnv, $skEnv] = $map[$channelCode];
    return [
        'url' => $url,
        'client_key' => trim((string)($_ENV[$ckEnv] ?? getenv($ckEnv) ?: '')),
        'secret_key' => trim((string)($_ENV[$skEnv] ?? getenv($skEnv) ?: '')),
    ];
}

/**
 * Ensure `moph_notify_channels` table exists in `digital` DB ($pdo2) and seed system rows once.
 */
function ensureMophNotifyChannelsTable(PDO $pdo2): void
{
    static $initialized = false;
    if ($initialized) {
        return;
    }

    $sql = "CREATE TABLE IF NOT EXISTS `moph_notify_channels` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `channel_code` VARCHAR(64) NOT NULL UNIQUE,
        `channel_name` VARCHAR(150) NOT NULL,
        `description` VARCHAR(255) DEFAULT NULL,
        `notify_url` VARCHAR(500) NOT NULL DEFAULT 'https://morpromt2f.moph.go.th/api/notify/send',
        `client_key` VARCHAR(255) DEFAULT NULL,
        `secret_key` VARCHAR(255) DEFAULT NULL,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `is_system` TINYINT(1) NOT NULL DEFAULT 0,
        `sort_order` INT NOT NULL DEFAULT 100,
        `updated_by` INT DEFAULT NULL,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo2->exec($sql);

    $count = (int)$pdo2->query("SELECT COUNT(*) FROM moph_notify_channels")->fetchColumn();
    if ($count === 0) {
        $defaults = [
            [
                'code' => 'it',
                'name' => 'กลุ่มงานดิจิทัล / IT',
                'desc' => 'แจ้งเตือนกลุ่มงานดิจิทัล (เวร / ลา / ไปราชการ / ไอที)',
                'order' => 10,
            ],
            [
                'code' => 'admin',
                'name' => 'กลุ่มงานบริหารทั่วไป',
                'desc' => 'แจ้งเตือนกลุ่มงานบริหารทั่วไปและธุรการ',
                'order' => 20,
            ],
            [
                'code' => 'opdcard',
                'name' => 'กลุ่มห้องบัตร (OPD Card)',
                'desc' => 'แจ้งเตือนกลุ่ม LINE ห้องบัตรโดยเฉพาะ',
                'order' => 30,
            ],
        ];

        $ins = $pdo2->prepare("
            INSERT INTO moph_notify_channels
                (channel_code, channel_name, description, notify_url, client_key, secret_key, is_active, is_system, sort_order)
            VALUES
                (:code, :name, :desc, :url, :ck, :sk, 1, 1, :sort_order)
        ");

        foreach ($defaults as $item) {
            $env = getMophEnvFallback($item['code']);
            $ins->execute([
                ':code' => $item['code'],
                ':name' => $item['name'],
                ':desc' => $item['desc'],
                ':url' => $env['url'],
                ':ck' => $env['client_key'] !== '' ? $env['client_key'] : null,
                ':sk' => $env['secret_key'] !== '' ? $env['secret_key'] : null,
                ':sort_order' => $item['order'],
            ]);
        }
    }

    $initialized = true;
}

function maskMophSecretValue(?string $value): string
{
    $v = trim((string)$value);
    if ($v === '') {
        return '';
    }
    $len = strlen($v);
    if ($len <= 8) {
        return str_repeat('*', $len);
    }
    return substr($v, 0, 4) . str_repeat('*', min(12, $len - 8)) . substr($v, -4);
}

/**
 * Format channel row safely for API responses (never returns plaintext keys).
 */
function formatMophChannelForApi(array $row, bool $includeMaskedKeys = true): array
{
    $code = (string)$row['channel_code'];
    $dbCk = trim((string)($row['client_key'] ?? ''));
    $dbSk = trim((string)($row['secret_key'] ?? ''));
    $hasDbKeys = ($dbCk !== '' && $dbSk !== '');

    $env = getMophEnvFallback($code);
    $hasEnvKeys = ($env['client_key'] !== '' && $env['secret_key'] !== '');

    $effCk = $hasDbKeys ? $dbCk : ($hasEnvKeys ? $env['client_key'] : '');
    $effSk = $hasDbKeys ? $dbSk : ($hasEnvKeys ? $env['secret_key'] : '');

    $keySource = $hasDbKeys ? 'database' : ($hasEnvKeys ? 'env' : 'none');

    $formatted = [
        'id' => (int)$row['id'],
        'channel_code' => $code,
        'channel_name' => (string)$row['channel_name'],
        'description' => (string)($row['description'] ?? ''),
        'notify_url' => trim((string)($row['notify_url'] ?? '')) ?: DEFAULT_MOPH_NOTIFY_URL,
        'is_active' => !empty($row['is_active']),
        'is_system' => !empty($row['is_system']),
        'sort_order' => (int)($row['sort_order'] ?? 100),
        'has_db_keys' => $hasDbKeys,
        'has_keys' => ($effCk !== '' && $effSk !== ''),
        'key_source' => $keySource,
        'updated_at' => $row['updated_at'] ?? null,
    ];

    if ($includeMaskedKeys) {
        $formatted['client_key_masked'] = maskMophSecretValue($effCk);
        $formatted['secret_key_masked'] = maskMophSecretValue($effSk);
    }

    return $formatted;
}

function getMophNotifyChannels(PDO $pdo2, bool $onlyActive = false, bool $includeMaskedKeys = true): array
{
    ensureMophNotifyChannelsTable($pdo2);
    $where = $onlyActive ? 'WHERE is_active = 1' : '';
    $stmt = $pdo2->query("SELECT * FROM moph_notify_channels {$where} ORDER BY sort_order ASC, id ASC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return array_map(
        static fn(array $r) => formatMophChannelForApi($r, $includeMaskedKeys),
        $rows
    );
}

/**
 * Resolve effective credentials (DB preferred, .env fallback for system channels).
 */
function resolveEffectiveMophChannel(PDO $pdo2, string $channelCode, bool $allowInactive = false): ?array
{
    ensureMophNotifyChannelsTable($pdo2);

    $stmt = $pdo2->prepare("SELECT * FROM moph_notify_channels WHERE channel_code = :code LIMIT 1");
    $stmt->execute([':code' => $channelCode]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        if (!$allowInactive && empty($row['is_active'])) {
            return null;
        }
        $dbCk = trim((string)($row['client_key'] ?? ''));
        $dbSk = trim((string)($row['secret_key'] ?? ''));
        $url = trim((string)($row['notify_url'] ?? '')) ?: DEFAULT_MOPH_NOTIFY_URL;

        if ($dbCk !== '' && $dbSk !== '') {
            return [
                'channel_code' => $channelCode,
                'channel_name' => (string)$row['channel_name'],
                'url' => $url,
                'client_key' => $dbCk,
                'secret_key' => $dbSk,
                'source' => 'database',
            ];
        }

        $env = getMophEnvFallback($channelCode);
        return [
            'channel_code' => $channelCode,
            'channel_name' => (string)$row['channel_name'],
            'url' => $url ?: $env['url'],
            'client_key' => $env['client_key'],
            'secret_key' => $env['secret_key'],
            'source' => ($env['client_key'] !== '' && $env['secret_key'] !== '') ? 'env' : 'none',
        ];
    }

    // Legacy fallback if caller passes an env-backed channel code directly
    $env = getMophEnvFallback($channelCode);
    if ($env['client_key'] !== '' && $env['secret_key'] !== '') {
        return [
            'channel_code' => $channelCode,
            'channel_name' => $channelCode,
            'url' => $env['url'],
            'client_key' => $env['client_key'],
            'secret_key' => $env['secret_key'],
            'source' => 'env',
        ];
    }

    return null;
}

/**
 * Send MOPH Notify HTTP POST payload.
 */
function sendMophNotifyRequest(string $url, string $clientKey, string $secretKey, array $payload): array
{
    $headers = [
        'Content-Type: application/json',
        "client-key: {$clientKey}",
        "secret-key: {$secretKey}",
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $raw = curl_exec($ch);
    $errno = curl_errno($ch);
    $err = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);

    $decoded = is_string($raw) ? json_decode($raw, true) : null;
    $ok = ($errno === 0 && $httpCode >= 200 && $httpCode < 300);

    return [
        'ok' => $ok,
        'http_code' => $httpCode,
        'curl_errno' => $errno,
        'curl_error' => $err,
        'raw' => is_string($raw) ? $raw : '',
        'decoded' => $decoded,
    ];
}

/**
 * Resolve channel from `digital.moph_notify_channels` (with .env fallback) and send MOPH Notify payload.
 */
function sendMophNotifyToChannel(PDO $pdo2, string $channelCode, array $payload): array
{
    $creds = resolveEffectiveMophChannel($pdo2, $channelCode, false);
    if (!$creds || $creds['client_key'] === '' || $creds['secret_key'] === '') {
        return [
            'ok' => false,
            'http_code' => 0,
            'curl_errno' => 0,
            'curl_error' => "Channel '{$channelCode}' is inactive or missing credentials",
            'raw' => '',
            'decoded' => null,
            'source' => $creds['source'] ?? 'none',
            'client_key_masked' => '',
        ];
    }

    $res = sendMophNotifyRequest($creds['url'], $creds['client_key'], $creds['secret_key'], $payload);
    $res['source'] = $creds['source'];
    $res['client_key_masked'] = maskMophSecretValue($creds['client_key']);
    return $res;
}


