<?php
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';
require_once __DIR__ . '/daily_notify_service.php';

$userData = authGuard();
$userId = (int)($userData['uid'] ?? 0);

$isAdmin = false;
try {
    $accessProfile = getUserAccessProfile($pdo3, $userId);
    $perms = $accessProfile['perms'];
    $deptName = $accessProfile['dept_name'];
    $isAdmin = $accessProfile['is_admin'];

    $isPermitted = $isAdmin
        || in_array('menu_it_schedule', $perms, true)
        || mb_strpos($deptName, 'สุขภาพดิจิทัล') !== false
        || mb_strpos($deptName, 'กลุ่มงานประกันสุขภาพ ยุทธศาสตร์') !== false;

    if (!$isPermitted) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์จัดการการแจ้งเตือนตารางเวร']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to check authorization']);
    exit;
}

function sanitizeSettingsForResponse(array $settings, bool $isAdmin = false): array
{
    $ck = trim((string)($settings['custom_client_key'] ?? ''));
    $sk = trim((string)($settings['custom_secret_key'] ?? ''));

    return [
        'channel_mode' => $settings['channel_mode'] ?? 'it',
        'has_custom_keys' => ($ck !== '' && $sk !== ''),
        'custom_client_key_masked' => $isAdmin ? maskMophSecretValue($ck) : '',
        'dept_ids' => $settings['dept_ids'] ?? '14,27',
        'include_unapproved' => !empty($settings['include_unapproved']),
        'notify_time' => $settings['notify_time'] ?? '07:30',
        'is_enabled' => !empty($settings['is_enabled']),
        'last_sent_date' => $settings['last_sent_date'] ?? null,
        'last_sent_at' => $settings['last_sent_at'] ?? null,
        'last_status' => $settings['last_status'] ?? null,
        'last_response' => $settings['last_response'] ?? null,
    ];
}

function getEnvChannelsStatus(): array
{
    $result = [];
    foreach (['it', 'admin', 'opdcard'] as $code) {
        $env = getMophEnvFallback($code);
        $result[$code] = ($env['client_key'] !== '' && $env['secret_key'] !== '');
    }
    return $result;
}

function getSchedulerStatus(): array
{
    $out = @shell_exec('schtasks /Query /TN ViteDigitalDailyScheduleNotify /FO LIST 2>&1');
    if (is_string($out) && stripos($out, 'ViteDigitalDailyScheduleNotify') !== false) {
        $enabled = stripos($out, 'Disabled') === false || preg_match('/Scheduled Task State:\s*Enabled/i', $out) === 1;
        return [
            'registered' => true,
            'enabled' => $enabled,
            'task_name' => 'ViteDigitalDailyScheduleNotify',
        ];
    }
    return [
        'registered' => false,
        'enabled' => false,
        'task_name' => 'ViteDigitalDailyScheduleNotify',
    ];
}

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $targetDate = isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])
            ? $_GET['date']
            : date('Y-m-d');

        $settings = getScheduleNotifySettings($pdo2);
        $previewSettings = $settings;

        if (isset($_GET['dept_ids'])) {
            $previewSettings['dept_ids'] = sanitizeDeptIdsInput((string)$_GET['dept_ids']);
        }
        if (isset($_GET['include_unapproved'])) {
            $previewSettings['include_unapproved'] = (int)(bool)$_GET['include_unapproved'];
        }

        $depts = $pdo3->query("SELECT HR_DEPARTMENT_SUB_ID, HR_DEPARTMENT_SUB_NAME FROM hr_department_sub ORDER BY HR_DEPARTMENT_SUB_ID ASC")
            ->fetchAll(PDO::FETCH_ASSOC);

        $summary = getDailyScheduleSummary($pdo2, $pdo3, $targetDate, $previewSettings);

        echo json_encode([
            'status' => 'success',
            'is_admin' => $isAdmin,
            'settings' => sanitizeSettingsForResponse($settings, $isAdmin),
            'channels' => getMophNotifyChannels($pdo2, true, $isAdmin),
            'env_channels' => getEnvChannelsStatus(),
            'scheduler' => getSchedulerStatus(),
            'departments' => $depts,
            'preview' => $summary,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'POST') {
        $input = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = [];
        }
        $action = $input['action'] ?? 'send_now';

        if ($action === 'save_settings') {
            $updated = saveScheduleNotifySettings($pdo2, $input, $isAdmin);
            $targetDate = !empty($input['target_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$input['target_date'])
                ? (string)$input['target_date']
                : date('Y-m-d');
            $summary = getDailyScheduleSummary($pdo2, $pdo3, $targetDate, $updated);

            echo json_encode([
                'status' => 'success',
                'message' => 'บันทึกการตั้งค่าแจ้งเตือนเรียบร้อยแล้ว',
                'is_admin' => $isAdmin,
                'settings' => sanitizeSettingsForResponse($updated, $isAdmin),
                'channels' => getMophNotifyChannels($pdo2, true, $isAdmin),
                'scheduler' => getSchedulerStatus(),
                'preview' => $summary,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'send_now') {
            // Optional inline setting updates before immediate send
            if (!empty($input['save_before_send'])) {
                saveScheduleNotifySettings($pdo2, $input, $isAdmin);
            }

            $targetDate = !empty($input['target_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$input['target_date'])
                ? (string)$input['target_date']
                : date('Y-m-d');

            $result = dispatchDailyScheduleNotify($pdo2, $pdo3, [
                'force' => true,
                'target_date' => $targetDate,
            ]);
            $updated = getScheduleNotifySettings($pdo2);
            $result['settings'] = sanitizeSettingsForResponse($updated, $isAdmin);

            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            exit;
        }

        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

