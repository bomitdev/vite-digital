<?php
/**
 * Daily Schedule MOPH Notify Service
 *
 * Shared business logic for querying:
 * 1. OPD Card (ห้องบัตร) duties for today and tomorrow ($pdo2)
 * 2. Staff on leave today ($pdo3)
 * 3. Staff on official trips (ไปราชการ) today ($pdo3)
 * and sending formatted Flex messages via MOPH Notify.
 */

date_default_timezone_set('Asia/Bangkok');

require_once __DIR__ . '/../../moph_notify_helper.php';

function ensureScheduleNotifyTable(PDO $pdo2): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    ensureMophNotifyChannelsTable($pdo2);

    $sql = "CREATE TABLE IF NOT EXISTS `schedule_notify_settings` (
        `id` TINYINT UNSIGNED NOT NULL PRIMARY KEY,
        `channel_mode` VARCHAR(64) NOT NULL DEFAULT 'it',
        `custom_client_key` VARCHAR(255) DEFAULT NULL,
        `custom_secret_key` VARCHAR(255) DEFAULT NULL,
        `dept_ids` VARCHAR(255) NOT NULL DEFAULT '14,27',
        `include_unapproved` TINYINT(1) NOT NULL DEFAULT 1,
        `notify_time` VARCHAR(10) NOT NULL DEFAULT '07:30',
        `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
        `last_sent_date` DATE DEFAULT NULL,
        `last_sent_at` DATETIME DEFAULT NULL,
        `last_status` VARCHAR(32) DEFAULT NULL,
        `last_response` TEXT DEFAULT NULL,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo2->exec($sql);

    // Widen legacy VARCHAR(32) column so custom DB channel codes up to 64 chars fit safely
    $col = $pdo2->query("SHOW COLUMNS FROM schedule_notify_settings LIKE 'channel_mode'")->fetch(PDO::FETCH_ASSOC);
    if ($col && stripos((string)($col['Type'] ?? ''), 'varchar(32)') !== false) {
        $pdo2->exec("ALTER TABLE schedule_notify_settings MODIFY COLUMN channel_mode VARCHAR(64) NOT NULL DEFAULT 'it'");
    }

    $stmt = $pdo2->query("SELECT COUNT(*) FROM schedule_notify_settings WHERE id = 1");
    if ((int)$stmt->fetchColumn() === 0) {
        $pdo2->exec("INSERT INTO schedule_notify_settings (id, channel_mode, dept_ids, include_unapproved, notify_time, is_enabled)
                     VALUES (1, 'it', '14,27', 1, '07:30', 1)");
    }

    $ensured = true;
}

function getScheduleNotifySettings(PDO $pdo2): array
{
    ensureScheduleNotifyTable($pdo2);
    $stmt = $pdo2->query("SELECT * FROM schedule_notify_settings WHERE id = 1 LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: [
        'id' => 1,
        'channel_mode' => 'it',
        'custom_client_key' => '',
        'custom_secret_key' => '',
        'dept_ids' => '14,27',
        'include_unapproved' => 1,
        'notify_time' => '07:30',
        'is_enabled' => 1,
        'last_sent_date' => null,
        'last_sent_at' => null,
        'last_status' => null,
        'last_response' => null,
    ];
}

function sanitizeDeptIdsInput(string $raw): string
{
    $trimmed = strtoupper(trim($raw));
    if ($trimmed === 'ALL' || $trimmed === '') {
        return $trimmed === 'ALL' ? 'ALL' : '14,27';
    }
    $parts = array_filter(array_map('intval', explode(',', $raw)), static fn(int $v) => $v > 0);
    $parts = array_values(array_unique($parts));
    return !empty($parts) ? implode(',', $parts) : '14,27';
}

function saveScheduleNotifySettings(PDO $pdo2, array $input, bool $allowKeyEdit = true): array
{
    ensureScheduleNotifyTable($pdo2);
    $current = getScheduleNotifySettings($pdo2);

    $activeChannels = getMophNotifyChannels($pdo2, true, false);
    $allowedModes = array_merge(['custom'], array_map(static fn(array $c) => $c['channel_code'], $activeChannels));
    $candidateMode = strtolower(trim((string)($input['channel_mode'] ?? '')));

    $channelMode = ($candidateMode !== '' && in_array($candidateMode, $allowedModes, true))
        ? $candidateMode
        : ($current['channel_mode'] ?? 'it');

    $deptIds = isset($input['dept_ids'])
        ? sanitizeDeptIdsInput((string)$input['dept_ids'])
        : ($current['dept_ids'] ?? '14,27');

    $includeUnapproved = isset($input['include_unapproved']) ? ((int)(bool)$input['include_unapproved']) : (int)$current['include_unapproved'];
    $isEnabled = isset($input['is_enabled']) ? ((int)(bool)$input['is_enabled']) : (int)$current['is_enabled'];

    $notifyTime = isset($input['notify_time']) && preg_match('/^\d{2}:\d{2}$/', (string)$input['notify_time'])
        ? (string)$input['notify_time']
        : ($current['notify_time'] ?? '07:30');

    $customClientKey = (string)($current['custom_client_key'] ?? '');
    $customSecretKey = (string)($current['custom_secret_key'] ?? '');

    if ($allowKeyEdit) {
        if (isset($input['custom_client_key']) && trim((string)$input['custom_client_key']) !== '') {
            $customClientKey = trim((string)$input['custom_client_key']);
        }
        $incomingSecret = isset($input['custom_secret_key']) ? trim((string)$input['custom_secret_key']) : '';
        if ($incomingSecret !== '') {
            $customSecretKey = $incomingSecret;
        }
        if ($channelMode !== 'custom' && !empty($input['clear_custom_keys'])) {
            $customClientKey = '';
            $customSecretKey = '';
        }
    }

    // If user schedules a new future time today, clear last_sent_date so it can fire again at the new time
    $oldNotifyTime = (string)($current['notify_time'] ?? '');
    $resetSentDate = ($notifyTime !== $oldNotifyTime && $notifyTime > date('H:i'));

    $sql = "UPDATE schedule_notify_settings
            SET channel_mode = :channel_mode,
                custom_client_key = :custom_client_key,
                custom_secret_key = :custom_secret_key,
                dept_ids = :dept_ids,
                include_unapproved = :include_unapproved,
                notify_time = :notify_time,
                is_enabled = :is_enabled" . ($resetSentDate ? ", last_sent_date = NULL" : "") . "
            WHERE id = 1";

    $stmt = $pdo2->prepare($sql);
    $stmt->execute([
        ':channel_mode' => $channelMode,
        ':custom_client_key' => $customClientKey !== '' ? $customClientKey : null,
        ':custom_secret_key' => $customSecretKey !== '' ? $customSecretKey : null,
        ':dept_ids' => $deptIds,
        ':include_unapproved' => $includeUnapproved,
        ':notify_time' => $notifyTime,
        ':is_enabled' => $isEnabled,
    ]);

    return getScheduleNotifySettings($pdo2);
}

function resolveMophNotifyCredentials(PDO $pdo2, array $settings): array
{
    $defaultUrl = trim((string)($_ENV['MOPH_NOTIFY_URL'] ?? getenv('MOPH_NOTIFY_URL') ?: '')) ?: DEFAULT_MOPH_NOTIFY_URL;
    $mode = strtolower(trim((string)($settings['channel_mode'] ?? 'it')));

    if ($mode === 'custom') {
        $ck = trim((string)($settings['custom_client_key'] ?? ''));
        $sk = trim((string)($settings['custom_secret_key'] ?? ''));
        return [
            'url' => $defaultUrl,
            'client_key' => $ck,
            'secret_key' => $sk,
            'channel_label' => 'กลุ่ม LINE ที่กำหนดเอง (Custom)',
        ];
    }

    $resolved = resolveEffectiveMophChannel($pdo2, $mode, false);
    if ($resolved) {
        $sourceNote = $resolved['source'] === 'database' ? 'ฐานข้อมูล' : '.env';
        return [
            'url' => $resolved['url'],
            'client_key' => $resolved['client_key'],
            'secret_key' => $resolved['secret_key'],
            'channel_label' => "{$resolved['channel_name']} ({$sourceNote})",
        ];
    }

    return [
        'url' => $defaultUrl,
        'client_key' => '',
        'secret_key' => '',
        'channel_label' => $mode !== '' ? $mode : 'ไม่พบช่องทาง',
    ];
}

function cleanThaiPersonName(string $name): string
{
    return trim((string)preg_replace('/\s+/', ' ', $name));
}

function formatThaiDateShort(string $ymd): string
{
    $ts = strtotime($ymd);
    if (!$ts) {
        return $ymd;
    }
    $months = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
        5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
        9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.',
    ];
    $d = (int)date('j', $ts);
    $m = $months[(int)date('n', $ts)] ?? '';
    $y = (int)date('Y', $ts) + 543;
    return "{$d} {$m} {$y}";
}

function formatThaiDateFull(string $ymd): string
{
    $ts = strtotime($ymd);
    if (!$ts) {
        return $ymd;
    }
    $days = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
    $dayName = $days[(int)date('w', $ts)] ?? '';
    return "วัน{$dayName}ที่ " . formatThaiDateShort($ymd);
}

function getDailyScheduleSummary(PDO $pdo2, PDO $pdo3, string $targetDate, array $settings): array
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $targetDate)) {
        $targetDate = date('Y-m-d');
    }
    $tomorrowDate = date('Y-m-d', strtotime($targetDate . ' +1 day'));

    // 1. OPD Card Duties Today & Tomorrow ($pdo2)
    $stmtOpd = $pdo2->prepare("
        SELECT
            d.date AS duty_date,
            d.is_special,
            e.id AS employee_id,
            e.name,
            e.position,
            e.phone
        FROM duties_opdcard d
        INNER JOIN employees_opdcard e ON d.employees_opdcard_id = e.id
        WHERE d.date IN (:today, :tomorrow)
        ORDER BY d.date ASC, d.is_special ASC, e.id ASC
    ");
    $stmtOpd->execute([':today' => $targetDate, ':tomorrow' => $tomorrowDate]);
    $opdRows = $stmtOpd->fetchAll(PDO::FETCH_ASSOC);

    $opdToday = [];
    $opdTomorrow = [];
    foreach ($opdRows as $row) {
        $entry = [
            'employee_id' => (int)$row['employee_id'],
            'name' => cleanThaiPersonName((string)$row['name']),
            'position' => trim((string)($row['position'] ?? '')),
            'phone' => trim((string)($row['phone'] ?? '')),
            'is_special' => (int)($row['is_special'] ?? 0),
        ];
        if ($row['duty_date'] === $targetDate) {
            $opdToday[] = $entry;
        } else {
            $opdTomorrow[] = $entry;
        }
    }

    // Department filter helper
    $deptRaw = trim((string)($settings['dept_ids'] ?? '14,27'));
    $isAllDepts = (strtoupper($deptRaw) === 'ALL');
    $deptIntList = [];
    if (!$isAllDepts) {
        $deptIntList = array_values(array_unique(array_filter(array_map('intval', explode(',', $deptRaw)), static fn(int $v) => $v > 0)));
        if (empty($deptIntList)) {
            $deptIntList = [14, 27];
        }
    }
    $deptSqlList = implode(',', $deptIntList);
    $includeUnapproved = !empty($settings['include_unapproved']);

    // 2. Leaves Today ($pdo3) - excludes canceled, rejected ('X','N','D'), and resignation ('12' / 3000-01-01)
    $leaveDeptClause = $isAllDepts ? '' : "AND p.HR_DEPARTMENT_SUB_ID IN ({$deptSqlList})";
    $leaveStatusClause = $includeUnapproved
        ? "AND lr.LEAVE_STATUS_CODE NOT IN ('X', 'N', 'D')"
        : "AND lr.LEAVE_STATUS_CODE = 'Z'";

    $sqlLeave = "
        SELECT
            lr.ID AS leave_id,
            lr.LEAVE_PERSON_ID AS person_id,
            CONCAT(COALESCE(pf.HR_PREFIX_NAME, ''), p.HR_FNAME, ' ', p.HR_LNAME) AS person_name,
            COALESCE(hds.HR_DEPARTMENT_SUB_NAME, '') AS dept_name,
            COALESCE(lt.LEAVE_TYPE_NAME, lr.LEAVE_TYPE_CODE) AS leave_type,
            lr.LEAVE_TYPE_CODE,
            lr.LEAVE_DATE_BEGIN AS start_date,
            lr.LEAVE_DATE_END AS end_date,
            COALESCE(lr.LEAVE_BECAUSE, '') AS reason,
            lr.LEAVE_STATUS_CODE AS status_code
        FROM leave_register lr
        LEFT JOIN leave_type lt ON lr.LEAVE_TYPE_CODE = lt.LEAVE_TYPE_ID
        INNER JOIN hr_person p ON lr.LEAVE_PERSON_ID = p.ID
        LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
        LEFT JOIN hr_department_sub hds ON p.HR_DEPARTMENT_SUB_ID = hds.HR_DEPARTMENT_SUB_ID
        WHERE lr.LEAVE_CANCEL_STATUS = 'False'
          AND lr.LEAVE_TYPE_CODE != '12'
          AND lr.LEAVE_DATE_END < '2999-01-01'
          {$leaveStatusClause}
          {$leaveDeptClause}
          AND (:target_date BETWEEN lr.LEAVE_DATE_BEGIN AND lr.LEAVE_DATE_END)
        ORDER BY hds.HR_DEPARTMENT_SUB_NAME ASC, p.HR_FNAME ASC
    ";
    $stmtLeave = $pdo3->prepare($sqlLeave);
    $stmtLeave->execute([':target_date' => $targetDate]);
    $leaveRows = $stmtLeave->fetchAll(PDO::FETCH_ASSOC);

    $leavesToday = [];
    foreach ($leaveRows as $lr) {
        $leavesToday[] = [
            'leave_id' => (int)$lr['leave_id'],
            'person_name' => cleanThaiPersonName((string)$lr['person_name']),
            'dept_name' => trim((string)$lr['dept_name']),
            'leave_type' => trim((string)$lr['leave_type']),
            'start_date' => $lr['start_date'],
            'end_date' => $lr['end_date'],
            'date_range_thai' => $lr['start_date'] === $lr['end_date']
                ? formatThaiDateShort($lr['start_date'])
                : formatThaiDateShort($lr['start_date']) . ' - ' . formatThaiDateShort($lr['end_date']),
            'reason' => trim((string)$lr['reason']),
            'status_code' => $lr['status_code'],
            'is_approved' => ($lr['status_code'] === 'Z'),
        ];
    }

    // 3. Official Trips Today ($pdo3) - checks both organizer (HR_ID) and attendees (record_index_person)
    $tripDeptClause = $isAllDepts
        ? ''
        : "AND (
            p.HR_DEPARTMENT_SUB_ID IN ({$deptSqlList})
            OR EXISTS (
                SELECT 1
                FROM record_index_person rip2
                INNER JOIN hr_person p2 ON rip2.HR_PERSON_ID = p2.ID
                WHERE rip2.RECORD_ID = ri.ID
                  AND p2.HR_DEPARTMENT_SUB_ID IN ({$deptSqlList})
            )
        )";
    $tripStatusClause = $includeUnapproved
        ? "AND (ri.STATUS IS NULL OR ri.STATUS NOT IN ('NOSUCCESS', 'NO'))"
        : "AND ri.STATUS = 'SUCCESS'";

    $sqlTrip = "
        SELECT
            ri.ID AS trip_id,
            CONCAT(COALESCE(pf.HR_PREFIX_NAME, ''), p.HR_FNAME, ' ', p.HR_LNAME) AS person_name,
            COALESCE(hds.HR_DEPARTMENT_SUB_NAME, '') AS dept_name,
            COALESCE(ri.RECORD_HEAD_USE, '') AS title,
            COALESCE(ri.PROVINCE_NAME, '') AS province,
            ri.STATUS AS status_code,
            COALESCE(NULLIF(NULLIF(CAST(ri.DATE_TRAVEL_GO AS CHAR), ''), '0000-00-00'), CAST(ri.DATE_GO AS CHAR)) AS effective_start,
            COALESCE(NULLIF(NULLIF(CAST(ri.DATE_TRAVEL_BACK AS CHAR), ''), '0000-00-00'), CAST(ri.DATE_BACK AS CHAR)) AS effective_end,
            (SELECT GROUP_CONCAT(rip.HR_FULLNAME ORDER BY rip.ID SEPARATOR ', ') FROM record_index_person rip WHERE rip.RECORD_ID = ri.ID) AS attendees
        FROM record_index ri
        INNER JOIN hr_person p ON ri.HR_ID = p.ID
        LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
        LEFT JOIN hr_department_sub hds ON p.HR_DEPARTMENT_SUB_ID = hds.HR_DEPARTMENT_SUB_ID
        WHERE (ri.CANCEL_STATUS IS NULL OR ri.CANCEL_STATUS = '' OR ri.CANCEL_STATUS = 'False')
          {$tripStatusClause}
          {$tripDeptClause}
          AND (:target_date BETWEEN
              COALESCE(NULLIF(NULLIF(CAST(ri.DATE_TRAVEL_GO AS CHAR), ''), '0000-00-00'), CAST(ri.DATE_GO AS CHAR))
              AND COALESCE(NULLIF(NULLIF(CAST(ri.DATE_TRAVEL_BACK AS CHAR), ''), '0000-00-00'), CAST(ri.DATE_BACK AS CHAR))
          )
        GROUP BY ri.ID
        ORDER BY ri.ID ASC
    ";
    $stmtTrip = $pdo3->prepare($sqlTrip);
    $stmtTrip->execute([':target_date' => $targetDate]);
    $tripRows = $stmtTrip->fetchAll(PDO::FETCH_ASSOC);

    $tripsToday = [];
    foreach ($tripRows as $tr) {
        $attendeeRaw = cleanThaiPersonName((string)($tr['attendees'] ?? ''));
        $attendeeList = $attendeeRaw !== ''
            ? array_values(array_filter(array_map(static fn($s) => cleanThaiPersonName($s), explode(',', $attendeeRaw))))
            : [];
        $displayNames = !empty($attendeeList) ? implode(', ', $attendeeList) : cleanThaiPersonName((string)$tr['person_name']);

        $tripsToday[] = [
            'trip_id' => (int)$tr['trip_id'],
            'person_name' => cleanThaiPersonName((string)$tr['person_name']),
            'display_names' => $displayNames,
            'dept_name' => trim((string)$tr['dept_name']),
            'title' => trim((string)$tr['title']),
            'province' => trim((string)$tr['province']),
            'start_date' => $tr['effective_start'],
            'end_date' => $tr['effective_end'],
            'date_range_thai' => $tr['effective_start'] === $tr['effective_end']
                ? formatThaiDateShort($tr['effective_start'])
                : formatThaiDateShort($tr['effective_start']) . ' - ' . formatThaiDateShort($tr['effective_end']),
            'status_code' => $tr['status_code'],
            'is_approved' => ($tr['status_code'] === 'SUCCESS'),
        ];
    }

    return [
        'today_date' => $targetDate,
        'today_thai' => formatThaiDateFull($targetDate),
        'today_short' => formatThaiDateShort($targetDate),
        'tomorrow_date' => $tomorrowDate,
        'tomorrow_thai' => formatThaiDateFull($tomorrowDate),
        'tomorrow_short' => formatThaiDateShort($tomorrowDate),
        'dept_scope' => $isAllDepts ? 'ALL' : $deptSqlList,
        'opd_today' => $opdToday,
        'opd_tomorrow' => $opdTomorrow,
        'leaves_today' => $leavesToday,
        'trips_today' => $tripsToday,
    ];
}

function buildSectionItemRows(string $emptyText, array $rows): array
{
    if (empty($rows)) {
        return [
            [
                'type' => 'text',
                'text' => "- {$emptyText}",
                'size' => 'sm',
                'color' => '#8c8c8c',
                'wrap' => true,
            ]
        ];
    }
    return $rows;
}

function buildDailyNotifyFlexPayload(array $summary): array
{
    // Build OPD Card Today rows
    $opdTodayRows = [];
    foreach ($summary['opd_today'] as $idx => $p) {
        $tag = $p['is_special'] > 0 ? ' (เสริม/PT)' : '';
        $phone = $p['phone'] !== '' ? " • {$p['phone']}" : '';
        $opdTodayRows[] = [
            'type' => 'text',
            'text' => ($idx + 1) . ". {$p['name']}{$tag}{$phone}",
            'size' => 'sm',
            'color' => '#1e293b',
            'weight' => 'bold',
            'wrap' => true,
        ];
    }

    // Build OPD Card Tomorrow rows
    $opdTomorrowRows = [];
    foreach ($summary['opd_tomorrow'] as $idx => $p) {
        $tag = $p['is_special'] > 0 ? ' (เสริม/PT)' : '';
        $phone = $p['phone'] !== '' ? " • {$p['phone']}" : '';
        $opdTomorrowRows[] = [
            'type' => 'text',
            'text' => ($idx + 1) . ". {$p['name']}{$tag}{$phone}",
            'size' => 'sm',
            'color' => '#334155',
            'wrap' => true,
        ];
    }

    // Build Leaves Today rows
    $leaveRows = [];
    foreach ($summary['leaves_today'] as $idx => $lv) {
        $statusTag = $lv['is_approved'] ? '' : ' [รออนุมัติ]';
        $typeText = $lv['leave_type'] !== '' ? $lv['leave_type'] : 'ลา';
        $deptText = $lv['dept_name'] !== '' ? " ({$lv['dept_name']})" : '';
        $leaveRows[] = [
            'type' => 'text',
            'text' => ($idx + 1) . ". {$lv['person_name']} - {$typeText}{$statusTag}\n   ช่วง {$lv['date_range_thai']}{$deptText}",
            'size' => 'sm',
            'color' => '#92400e',
            'wrap' => true,
        ];
    }

    // Build Trips Today rows
    $tripRows = [];
    foreach ($summary['trips_today'] as $idx => $tr) {
        $statusTag = $tr['is_approved'] ? '' : ' [รออนุมัติ]';
        $subject = $tr['title'] !== '' ? $tr['title'] : 'ไปราชการ';
        $prov = $tr['province'] !== '' ? " ({$tr['province']})" : '';
        $tripRows[] = [
            'type' => 'text',
            'text' => ($idx + 1) . ". {$tr['display_names']}{$statusTag}\n   เรื่อง: {$subject}{$prov} • {$tr['date_range_thai']}",
            'size' => 'sm',
            'color' => '#1e40af',
            'wrap' => true,
        ];
    }

    $todayNames = !empty($summary['opd_today'])
        ? implode(', ', array_column($summary['opd_today'], 'name'))
        : 'ไม่มี';
    $tomorrowNames = !empty($summary['opd_tomorrow'])
        ? implode(', ', array_column($summary['opd_tomorrow'], 'name'))
        : 'ไม่มี';

    $altText = "แจ้งเตือนเวรห้องบัตร ({$summary['today_short']}): วันนี้ {$todayNames} | พรุ่งนี้ {$tomorrowNames} | ลา " . count($summary['leaves_today']) . " คน | ไปราชการ " . count($summary['trips_today']) . " รายการ";
    if (function_exists('mb_substr')) {
        $altText = mb_substr($altText, 0, 380, 'UTF-8');
    } else {
        $altText = substr($altText, 0, 380);
    }

    $bodyContents = array_merge(
        [
            [
                'type' => 'text',
                'text' => "🏥 เวรห้องบัตรวันนี้ ({$summary['today_short']})",
                'weight' => 'bold',
                'size' => 'sm',
                'color' => '#0f766e',
            ],
        ],
        buildSectionItemRows('ไม่มีผู้ขึ้นเวรห้องบัตรวันนี้', $opdTodayRows),
        [
            ['type' => 'separator', 'margin' => 'md'],
            [
                'type' => 'text',
                'text' => "📆 เวรห้องบัตรพรุ่งนี้ ({$summary['tomorrow_short']})",
                'weight' => 'bold',
                'size' => 'sm',
                'color' => '#0284c7',
                'margin' => 'md',
            ],
        ],
        buildSectionItemRows('ไม่มีผู้ขึ้นเวรห้องบัตรพรุ่งนี้', $opdTomorrowRows),
        [
            ['type' => 'separator', 'margin' => 'md'],
            [
                'type' => 'text',
                'text' => '📝 ผู้ลาวันนี้ (' . count($summary['leaves_today']) . ' คน)',
                'weight' => 'bold',
                'size' => 'sm',
                'color' => '#b45309',
                'margin' => 'md',
            ],
        ],
        buildSectionItemRows('ไม่มีผู้ลาวันนี้', $leaveRows),
        [
            ['type' => 'separator', 'margin' => 'md'],
            [
                'type' => 'text',
                'text' => '🚗 ไปราชการวันนี้ (' . count($summary['trips_today']) . ' รายการ)',
                'weight' => 'bold',
                'size' => 'sm',
                'color' => '#1d4ed8',
                'margin' => 'md',
            ],
        ],
        buildSectionItemRows('ไม่มีผู้ไปราชการวันนี้', $tripRows)
    );

    return [
        'messages' => [
            [
                'type' => 'flex',
                'altText' => $altText,
                'contents' => [
                    'type' => 'bubble',
                    'direction' => 'ltr',
                    'header' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'backgroundColor' => '#1e3a8a',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => '📋 แจ้งเตือนเวรห้องบัตร & การลา',
                                'weight' => 'bold',
                                'size' => 'md',
                                'color' => '#ffffff',
                                'wrap' => true,
                                'align' => 'center',
                            ],
                            [
                                'type' => 'text',
                                'text' => $summary['today_thai'],
                                'size' => 'xs',
                                'color' => '#bfdbfe',
                                'margin' => 'xs',
                                'wrap' => true,
                                'align' => 'center',
                            ],
                        ],
                    ],
                    'body' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'spacing' => 'sm',
                        'contents' => $bodyContents,
                    ],
                    'footer' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => 'กลุ่มงานสุขภาพดิจิทัล • รพ.ชานุมาน (MOPH Notify)',
                                'size' => 'xs',
                                'color' => '#888888',
                                'align' => 'center',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];
}

function dispatchDailyScheduleNotify(PDO $pdo2, PDO $pdo3, array $options = []): array
{
    $settings = getScheduleNotifySettings($pdo2);
    $targetDate = !empty($options['target_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$options['target_date'])
        ? (string)$options['target_date']
        : date('Y-m-d');
    $isAuto = !empty($options['auto']);
    $isForce = !empty($options['force']);
    $isDryRun = !empty($options['dry_run']);

    if ($isAuto && empty($settings['is_enabled'])) {
        return [
            'status' => 'skipped',
            'message' => 'การแจ้งเตือนอัตโนมัติถูกปิดใช้งานอยู่',
            'summary' => null,
        ];
    }

    $scheduledHm = trim((string)($settings['notify_time'] ?? '07:30'));
    $currentHm = date('H:i');
    if ($isAuto && !$isForce && !$isDryRun && preg_match('/^\d{2}:\d{2}$/', $scheduledHm) && $currentHm < $scheduledHm) {
        return [
            'status' => 'skipped',
            'message' => "ยังไม่ถึงเวลาที่ตั้งไว้ ({$scheduledHm} น. ขณะนี้ {$currentHm} น.)",
            'summary' => null,
        ];
    }

    if ($isAuto && !$isForce && ($settings['last_sent_date'] ?? '') === $targetDate && ($settings['last_status'] ?? '') === 'success') {
        $lastSentTime = !empty($settings['last_sent_at']) ? substr($settings['last_sent_at'], 11, 5) : '';
        if ($lastSentTime !== '' && $lastSentTime >= $scheduledHm) {
            return [
                'status' => 'skipped',
                'message' => "แจ้งเตือนของวันที่ {$targetDate} ถูกส่งไปแล้วก่อนหน้านี้ (เมื่อ {$settings['last_sent_at']})",
                'summary' => null,
            ];
        }
    }

    $summary = getDailyScheduleSummary($pdo2, $pdo3, $targetDate, $settings);
    $payload = buildDailyNotifyFlexPayload($summary);
    $creds = resolveMophNotifyCredentials($pdo2, $settings);

    if ($isDryRun) {
        return [
            'status' => 'dry_run',
            'message' => 'จำลองข้อความสำเร็จ (ไม่ได้ส่งจริง)',
            'channel' => $creds['channel_label'],
            'summary' => $summary,
            'payload' => $payload,
        ];
    }

    if ($creds['client_key'] === '' || $creds['secret_key'] === '') {
        return [
            'status' => 'error',
            'message' => 'ยังไม่ได้ตั้งค่า MOPH client-key หรือ secret-key สำหรับช่องทางที่เลือก',
            'summary' => $summary,
        ];
    }

    $res = sendMophNotifyRequest($creds['url'], $creds['client_key'], $creds['secret_key'], $payload);

    $statusText = $res['ok'] ? 'success' : 'error';
    $respExcerpt = mb_substr($res['raw'] !== '' ? $res['raw'] : $res['curl_error'], 0, 1000, 'UTF-8');

    $upd = $pdo2->prepare("UPDATE schedule_notify_settings
                           SET last_sent_date = :sent_date,
                               last_sent_at = NOW(),
                               last_status = :status,
                               last_response = :resp
                           WHERE id = 1");
    $upd->execute([
        ':sent_date' => $res['ok'] ? $targetDate : ($settings['last_sent_date'] ?? null),
        ':status' => $statusText,
        ':resp' => "HTTP {$res['http_code']}: {$respExcerpt}",
    ]);

    return [
        'status' => $statusText,
        'message' => $res['ok']
            ? "ส่งแจ้งเตือนเข้า LINE ผ่าน MOPH Notify เรียบร้อยแล้ว ({$creds['channel_label']})"
            : "ส่งไม่สำเร็จ (HTTP {$res['http_code']}): " . ($res['curl_error'] ?: $res['raw']),
        'channel' => $creds['channel_label'],
        'http_code' => $res['http_code'],
        'moph_response' => $res['decoded'] ?? $res['raw'],
        'summary' => $summary,
    ];
}
