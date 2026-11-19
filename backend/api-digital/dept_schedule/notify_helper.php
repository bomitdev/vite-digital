<?php
/**
 * Shared LINE Flex Message builder for Department Shift Schedules.
 */

function getThaiDayDateLabel(string $ymd): string
{
    $ts = strtotime($ymd);
    $thaiWeekdays = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์'];
    $thaiMonths = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    $w = $thaiWeekdays[(int)date('w', $ts)];
    $d = (int)date('j', $ts);
    $m = $thaiMonths[(int)date('n', $ts)];
    $y = (int)date('Y', $ts) + 543;
    return "วัน{$w}ที่ {$d} {$m} {$y}";
}

/**
 * Find up to $maxShiftDays upcoming days (starting from $startDate, scanning $scanDays ahead)
 * that have at least 1 scheduled shift in $groupId.
 */
function fetchUpcomingDeptShiftDays(PDO $pdo, int $groupId, string $startDate, int $maxShiftDays = 2, int $scanDays = 14): array
{
    $stmt = $pdo->prepare("
        SELECT m.person_name, m.phone, e.shift_code
        FROM dept_schedule_entries e
        JOIN dept_schedule_members m ON e.member_id = m.id
        WHERE e.group_id = ? AND e.duty_year = ? AND e.duty_month = ? AND e.duty_day = ? AND m.active = 1
        ORDER BY m.sort_order ASC, m.id ASC
    ");

    $result = [];
    for ($offset = 0; $offset < $scanDays; $offset++) {
        $ymd = date('Y-m-d', strtotime("{$startDate} +{$offset} days"));
        $y = (int)date('Y', strtotime($ymd));
        $m = (int)date('n', strtotime($ymd));
        $d = (int)date('j', strtotime($ymd));

        $stmt->execute([$groupId, $y, $m, $d]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $result[] = [
                'date' => $ymd,
                'label' => getThaiDayDateLabel($ymd),
                'entries' => $rows,
            ];
            if (count($result) >= $maxShiftDays) {
                break;
            }
        }
    }
    return $result;
}

function buildDeptScheduleFlexPayload(PDO $pdo, array $group, string $startDate, bool $isTest = false): ?array
{
    $groupId = (int)($group['id'] ?? 0);
    $shiftTypes = json_decode($group['shift_types_json'] ?? '', true);
    if (!is_array($shiftTypes)) {
        $shiftTypes = [];
    }
    $shiftMap = [];
    foreach ($shiftTypes as $st) {
        if (!empty($st['code'])) {
            $shiftMap[$st['code']] = [
                'label' => $st['label'] ?? $st['code'],
                'color' => $st['color'] ?? '#4f46e5',
            ];
        }
    }

    $daysList = $groupId > 0 ? fetchUpcomingDeptShiftDays($pdo, $groupId, $startDate, 2, 14) : [];

    if (empty($daysList) && !$isTest) {
        return null;
    }

    if (empty($daysList) && $isTest) {
        $daysList = [
            [
                'date' => $startDate,
                'label' => getThaiDayDateLabel($startDate),
                'entries' => [
                    ['person_name' => 'เจ้าหน้าที่ทดสอบ (ยังไม่มีเวรใน 14 วันข้างหน้า)', 'phone' => '08x-xxx-xxxx', 'shift_code' => 'ช']
                ]
            ]
        ];
    }

    $bodyContents = [];
    foreach ($daysList as $idx => $dayInfo) {
        if ($idx > 0) {
            $bodyContents[] = ['type' => 'separator', 'margin' => 'md', 'color' => '#e2e8f0'];
        }

        $bodyContents[] = [
            'type' => 'box',
            'layout' => 'vertical',
            'margin' => $idx > 0 ? 'md' : 'none',
            'backgroundColor' => '#f1f5f9',
            'cornerRadius' => '8px',
            'paddingAll' => '8px',
            'contents' => [
                [
                    'type' => 'text',
                    'text' => "📅 {$dayInfo['label']}",
                    'weight' => 'bold',
                    'size' => 'sm',
                    'color' => '#1e293b',
                    'wrap' => true,
                ]
            ]
        ];

        foreach ($dayInfo['entries'] as $entry) {
            $code = (string)$entry['shift_code'];
            $meta = $shiftMap[$code] ?? ['label' => $code, 'color' => '#4f46e5'];
            $color = preg_match('/^#[0-9a-fA-F]{6}$/', $meta['color']) ? $meta['color'] : '#4f46e5';
            $phoneTxt = !empty($entry['phone']) ? "📞 {$entry['phone']}" : '-';

            $bodyContents[] = [
                'type' => 'box',
                'layout' => 'horizontal',
                'margin' => 'sm',
                'alignItems' => 'center',
                'spacing' => 'sm',
                'contents' => [
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'backgroundColor' => $color,
                        'cornerRadius' => '6px',
                        'paddingTop' => '3px',
                        'paddingBottom' => '3px',
                        'paddingStart' => '8px',
                        'paddingEnd' => '8px',
                        'flex' => 0,
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => $code,
                                'color' => '#ffffff',
                                'size' => 'xs',
                                'weight' => 'bold',
                                'align' => 'center',
                            ]
                        ]
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'flex' => 1,
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => (string)$entry['person_name'],
                                'size' => 'sm',
                                'weight' => 'bold',
                                'color' => '#0f172a',
                                'wrap' => true,
                            ],
                            [
                                'type' => 'text',
                                'text' => "{$meta['label']} • {$phoneTxt}",
                                'size' => 'xs',
                                'color' => '#64748b',
                                'wrap' => true,
                            ]
                        ]
                    ]
                ]
            ];
        }
    }

    $titlePrefix = $isTest ? '🔔 [ทดสอบ] แจ้งเตือนตารางเวร' : '🔔 แจ้งเตือนตารางเวร';
    $groupTitle = (string)($group['title'] ?? 'ตารางเวรกลุ่มงาน');
    $deptName = (string)($group['department_name'] ?? '');

    return [
        'messages' => [
            [
                'type' => 'flex',
                'altText' => "{$titlePrefix}: {$groupTitle}",
                'contents' => [
                    'type' => 'bubble',
                    'size' => 'mega',
                    'header' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'backgroundColor' => '#5b21b6',
                        'paddingAll' => '16px',
                        'contents' => [
                            [
                                'type' => 'text',
                                'text' => $titlePrefix,
                                'color' => '#ddd6fe',
                                'size' => 'xs',
                                'weight' => 'bold',
                            ],
                            [
                                'type' => 'text',
                                'text' => $groupTitle,
                                'color' => '#ffffff',
                                'size' => 'md',
                                'weight' => 'bold',
                                'wrap' => true,
                                'margin' => 'xs',
                            ],
                            [
                                'type' => 'text',
                                'text' => $deptName !== '' ? $deptName : 'โรงพยาบาลชานุมาน',
                                'color' => '#ede9fe',
                                'size' => 'xs',
                                'wrap' => true,
                            ]
                        ]
                    ],
                    'body' => [
                        'type' => 'box',
                        'layout' => 'vertical',
                        'paddingAll' => '16px',
                        'contents' => $bodyContents,
                    ]
                ]
            ]
        ]
    ];
}

