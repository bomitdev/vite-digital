<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

function ensureDeptScheduleTables(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS dept_schedule_groups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            department_id INT NULL,
            department_name VARCHAR(255) NOT NULL,
            title VARCHAR(255) NOT NULL,
            notify_channel VARCHAR(100) NULL,
            notify_enabled TINYINT(1) DEFAULT 0,
            notify_days VARCHAR(50) DEFAULT '1,2,3,4,5,6,7',
            notify_time VARCHAR(5) DEFAULT '08:00',
            last_notified_date DATE NULL,
            shift_types_json TEXT NULL,
            active TINYINT(1) DEFAULT 1,
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $extraCols = [
        'notify_channel' => "ALTER TABLE dept_schedule_groups ADD COLUMN notify_channel VARCHAR(100) NULL AFTER title",
        'notify_enabled' => "ALTER TABLE dept_schedule_groups ADD COLUMN notify_enabled TINYINT(1) DEFAULT 0 AFTER notify_channel",
        'notify_days' => "ALTER TABLE dept_schedule_groups ADD COLUMN notify_days VARCHAR(50) DEFAULT '1,2,3,4,5,6,7' AFTER notify_enabled",
        'notify_time' => "ALTER TABLE dept_schedule_groups ADD COLUMN notify_time VARCHAR(5) DEFAULT '08:00' AFTER notify_days",
        'last_notified_date' => "ALTER TABLE dept_schedule_groups ADD COLUMN last_notified_date DATE NULL AFTER notify_time"
    ];
    foreach ($extraCols as $colName => $alterSql) {
        $colChk = $pdo->query("SHOW COLUMNS FROM dept_schedule_groups LIKE '$colName'")->fetch(PDO::FETCH_ASSOC);
        if (!$colChk) {
            $pdo->exec($alterSql);
        }
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS dept_schedule_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            person_id INT NULL,
            person_name VARCHAR(255) NOT NULL,
            phone VARCHAR(50) NULL,
            sort_order INT DEFAULT 0,
            active TINYINT(1) DEFAULT 1,
            INDEX idx_group (group_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS dept_schedule_entries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            member_id INT NOT NULL,
            duty_year INT NOT NULL,
            duty_month INT NOT NULL,
            duty_day INT NOT NULL,
            shift_code VARCHAR(50) NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_entry (group_id, member_id, duty_year, duty_month, duty_day),
            INDEX idx_period (group_id, duty_year, duty_month)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS dept_schedule_user_permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            group_id INT NOT NULL,
            user_id INT NOT NULL,
            user_name VARCHAR(255) NOT NULL,
            access_level ENUM('ADMIN', 'STAFF', 'VIEWER') DEFAULT 'STAFF',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_group_user (group_id, user_id),
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function getUserGroupAccessLevel(PDO $pdo, int $group_id, int $user_id, int $is_admin, int $user_dept_id = 0): ?string {
    if ($is_admin) return 'ADMIN';
    if (!$group_id) return null;
    if ($user_id > 0) {
        $stmt = $pdo->prepare("SELECT access_level FROM dept_schedule_user_permissions WHERE group_id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$group_id, $user_id]);
        $lvl = $stmt->fetchColumn();
        if ($lvl) return $lvl;
    }
    if ($user_dept_id > 0) {
        $stmtDept = $pdo->prepare("SELECT id FROM dept_schedule_groups WHERE id = ? AND department_id = ? AND active = 1 LIMIT 1");
        $stmtDept->execute([$group_id, $user_dept_id]);
        if ($stmtDept->fetchColumn()) {
            return 'VIEWER';
        }
    }
    return null;
}

function defaultShiftTypes() {
    return [
        ['code' => 'ช', 'label' => 'เวรเช้า', 'color' => '#0ea5e9'],
        ['code' => 'บ', 'label' => 'เวรบ่าย', 'color' => '#f59e0b'],
        ['code' => 'ด', 'label' => 'เวรดึก', 'color' => '#6366f1'],
        ['code' => 'Call', 'label' => 'On-Call', 'color' => '#10b981'],
    ];
}

try {
    ensureDeptScheduleTables($pdo2);

    $method = $_SERVER['REQUEST_METHOD'];
    $body = [];
    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) $body = $decoded;
    }

    $action = $_GET['action'] ?? ($body['action'] ?? 'groups');
    $req_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : (int)($body['user_id'] ?? 0);
    $req_is_admin = isset($_GET['is_admin']) ? (int)$_GET['is_admin'] : (int)($body['is_admin'] ?? 0);
    $req_dept_id = isset($_GET['user_department_id']) ? (int)$_GET['user_department_id'] : (isset($_GET['department_id']) ? (int)$_GET['department_id'] : (int)($body['user_department_id'] ?? 0));

    if ($action === 'moph_channels') {
        require_once __DIR__ . '/../../moph_notify_helper.php';
        ensureMophNotifyChannelsTable($pdo2);
        $stCh = $pdo2->query("SELECT channel_code, channel_name, description, is_active FROM moph_notify_channels WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
        echo json_encode(['status' => 'success', 'data' => $stCh->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'groups') {
        if ($req_is_admin) {
            $stmt = $pdo2->query("
                SELECT g.*,
                       'ADMIN' as access_level,
                       (SELECT COUNT(*) FROM dept_schedule_members m WHERE m.group_id = g.id AND m.active = 1) as member_count,
                       (SELECT COUNT(*) FROM dept_schedule_user_permissions p WHERE p.group_id = g.id) as user_count
                FROM dept_schedule_groups g
                WHERE g.active = 1
                ORDER BY g.department_name ASC, g.id ASC
            ");
            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($req_user_id > 0 || $req_dept_id > 0) {
            $stmt = $pdo2->prepare("
                SELECT g.*,
                       COALESCE(p.access_level, 'VIEWER') as access_level,
                       (SELECT COUNT(*) FROM dept_schedule_members m WHERE m.group_id = g.id AND m.active = 1) as member_count,
                       (SELECT COUNT(*) FROM dept_schedule_user_permissions p2 WHERE p2.group_id = g.id) as user_count
                FROM dept_schedule_groups g
                LEFT JOIN dept_schedule_user_permissions p ON p.group_id = g.id AND p.user_id = ?
                WHERE g.active = 1
                  AND (p.user_id IS NOT NULL OR (? > 0 AND g.department_id = ?))
                ORDER BY g.department_name ASC, g.id ASC
            ");
            $stmt->execute([$req_user_id, $req_dept_id, $req_dept_id]);
            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $groups = [];
        }

        foreach ($groups as &$g) {
            $shifts = json_decode($g['shift_types_json'] ?? '', true);
            $g['shift_types'] = is_array($shifts) && !empty($shifts) ? $shifts : defaultShiftTypes();
            $g['id'] = (int)$g['id'];
            $g['department_id'] = $g['department_id'] ? (int)$g['department_id'] : null;
            $g['member_count'] = (int)$g['member_count'];
            $g['user_count'] = (int)($g['user_count'] ?? 0);
            $g['notify_enabled'] = (int)($g['notify_enabled'] ?? 0);
            $g['notify_days'] = $g['notify_days'] ?: '1,2,3,4,5,6,7';
            $g['notify_time'] = $g['notify_time'] ?: '08:00';
        }
        echo json_encode(['status' => 'success', 'data' => $groups], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'group_users') {
        $group_id = (int)($_GET['group_id'] ?? 0);
        $stmt = $pdo2->prepare("
            SELECT id, group_id, user_id, user_name, access_level, created_at
            FROM dept_schedule_user_permissions
            WHERE group_id = ?
            ORDER BY FIELD(access_level, 'ADMIN', 'STAFF', 'VIEWER'), user_name ASC
        ");
        $stmt->execute([$group_id]);
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'save_group_user') {
        $group_id = (int)($body['group_id'] ?? 0);
        $target_user_id = (int)($body['target_user_id'] ?? 0);
        $target_user_name = trim($body['target_user_name'] ?? '');
        $access_level = strtoupper(trim($body['access_level'] ?? 'STAFF'));
        if (!in_array($access_level, ['ADMIN', 'STAFF', 'VIEWER'])) $access_level = 'STAFF';

        $callerAccess = getUserGroupAccessLevel($pdo2, $group_id, $req_user_id, $req_is_admin);
        if ($callerAccess !== 'ADMIN') {
            echo json_encode(['status' => 'error', 'message' => 'เฉพาะผู้ดูแลระบบหรือ Admin ของกลุ่มงานเท่านั้นที่กำหนดสิทธิ์ได้'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($group_id <= 0 || $target_user_id <= 0 || $target_user_name === '') {
            echo json_encode(['status' => 'error', 'message' => 'ข้อมูลผู้ใช้งานไม่ครบถ้วน'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $pdo2->prepare("
            INSERT INTO dept_schedule_user_permissions (group_id, user_id, user_name, access_level)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE user_name = VALUES(user_name), access_level = VALUES(access_level)
        ");
        $stmt->execute([$group_id, $target_user_id, $target_user_name, $access_level]);
        echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'remove_group_user') {
        $perm_id = (int)($body['id'] ?? 0);
        $group_id = (int)($body['group_id'] ?? 0);
        $callerAccess = getUserGroupAccessLevel($pdo2, $group_id, $req_user_id, $req_is_admin);
        if ($callerAccess !== 'ADMIN') {
            echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์ลบผู้ใช้งาน'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $stmt = $pdo2->prepare("DELETE FROM dept_schedule_user_permissions WHERE id = ? AND group_id = ?");
        $stmt->execute([$perm_id, $group_id]);
        echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'save_group') {
        $id = !empty($body['id']) ? (int)$body['id'] : 0;
        $department_id = !empty($body['department_id']) ? (int)$body['department_id'] : null;
        $department_name = trim($body['department_name'] ?? '');
        $title = trim($body['title'] ?? '');
        $notify_channel = trim((string)($body['notify_channel'] ?? ''));
        $notify_enabled = !empty($body['notify_enabled']) && $notify_channel !== '' ? 1 : 0;
        $notify_days_raw = $body['notify_days'] ?? '1,2,3,4,5,6,7';
        if (is_array($notify_days_raw)) {
            $notify_days = implode(',', array_filter(array_map('intval', $notify_days_raw), fn($d) => $d >= 1 && $d <= 7));
        } else {
            $notify_days = trim((string)$notify_days_raw);
        }
        if ($notify_days === '') $notify_days = '1,2,3,4,5,6,7';
        $notify_time = trim((string)($body['notify_time'] ?? '08:00'));
        if (!preg_match('/^\d{2}:\d{2}$/', $notify_time)) $notify_time = '08:00';
        $shift_types = $body['shift_types'] ?? defaultShiftTypes();
        $created_by = !empty($body['created_by']) ? (int)$body['created_by'] : null;

        if ($department_name === '' || $title === '') {
            echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุชื่อกลุ่มงานและชื่อตารางเวร'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $shiftJson = json_encode($shift_types, JSON_UNESCAPED_UNICODE);

        if ($id > 0) {
            $callerAccess = getUserGroupAccessLevel($pdo2, $id, $req_user_id, $req_is_admin);
            if ($callerAccess !== 'ADMIN') {
                echo json_encode(['status' => 'error', 'message' => 'เฉพาะผู้ดูแลตารางเวรกลุ่มงานเท่านั้นที่แก้ไขการตั้งค่าได้'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $stmt = $pdo2->prepare("
                UPDATE dept_schedule_groups
                SET department_id = ?, department_name = ?, title = ?, notify_channel = ?, notify_enabled = ?, notify_days = ?, notify_time = ?, shift_types_json = ?
                WHERE id = ?
            ");
            $stmt->execute([$department_id, $department_name, $title, $notify_channel !== '' ? $notify_channel : null, $notify_enabled, $notify_days, $notify_time, $shiftJson, $id]);
        } else {
            if (!$req_is_admin) {
                echo json_encode(['status' => 'error', 'message' => 'เฉพาะผู้ดูแลระบบเท่านั้นที่สร้างกลุ่มตารางเวรใหม่ได้'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $stmt = $pdo2->prepare("
                INSERT INTO dept_schedule_groups (department_id, department_name, title, notify_channel, notify_enabled, notify_days, notify_time, shift_types_json, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$department_id, $department_name, $title, $notify_channel !== '' ? $notify_channel : null, $notify_enabled, $notify_days, $notify_time, $shiftJson, $created_by]);
            $id = (int)$pdo2->lastInsertId();
        }

        echo json_encode(['status' => 'success', 'id' => $id], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'delete_group') {
        $id = (int)($body['id'] ?? 0);
        $callerAccess = getUserGroupAccessLevel($pdo2, $id, $req_user_id, $req_is_admin);
        if ($callerAccess !== 'ADMIN') {
            echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์ลบกลุ่มตารางเวรนี้'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($id > 0) {
            $stmt = $pdo2->prepare("UPDATE dept_schedule_groups SET active = 0 WHERE id = ?");
            $stmt->execute([$id]);
        }
        echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'get_schedule') {
        $group_id = (int)($_GET['group_id'] ?? 0);
        $year = (int)($_GET['year'] ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));

        $accessLevel = getUserGroupAccessLevel($pdo2, $group_id, $req_user_id, $req_is_admin, $req_dept_id);
        if (!$accessLevel) {
            echo json_encode(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์เข้าถึงตารางเวรกลุ่มงานนี้'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmtG = $pdo2->prepare("SELECT * FROM dept_schedule_groups WHERE id = ? AND active = 1");
        $stmtG->execute([$group_id]);
        $group = $stmtG->fetch(PDO::FETCH_ASSOC);
        if (!$group) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบกลุ่มตารางเวร'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $shifts = json_decode($group['shift_types_json'] ?? '', true);
        $group['shift_types'] = is_array($shifts) && !empty($shifts) ? $shifts : defaultShiftTypes();
        $group['access_level'] = $accessLevel;

        $stmtM = $pdo2->prepare("
            SELECT id, group_id, person_id, person_name, phone, sort_order
            FROM dept_schedule_members
            WHERE group_id = ? AND active = 1
            ORDER BY sort_order ASC, id ASC
        ");
        $stmtM->execute([$group_id]);
        $members = $stmtM->fetchAll(PDO::FETCH_ASSOC);

        $stmtE = $pdo2->prepare("
            SELECT member_id, duty_day, shift_code
            FROM dept_schedule_entries
            WHERE group_id = ? AND duty_year = ? AND duty_month = ?
        ");
        $stmtE->execute([$group_id, $year, $month]);
        $entriesMap = [];
        while ($row = $stmtE->fetch(PDO::FETCH_ASSOC)) {
            $mid = (string)$row['member_id'];
            $day = (string)$row['duty_day'];
            if (!isset($entriesMap[$mid])) $entriesMap[$mid] = [];
            $entriesMap[$mid][$day] = $row['shift_code'];
        }

        foreach ($members as &$m) {
            $m['id'] = (int)$m['id'];
            $m['person_id'] = $m['person_id'] ? (int)$m['person_id'] : null;
            $m['duties'] = $entriesMap[(string)$m['id']] ?? (object)[];
        }

        echo json_encode([
            'status' => 'success',
            'data' => [
                'group' => $group,
                'members' => $members
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'add_members') {
        $group_id = (int)($body['group_id'] ?? 0);
        $callerAccess = getUserGroupAccessLevel($pdo2, $group_id, $req_user_id, $req_is_admin);
        if (!in_array($callerAccess, ['ADMIN', 'STAFF'])) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์แก้ไขรายชื่อในตารางเวรนี้'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $items = $body['members'] ?? [];
        if ($group_id <= 0 || !is_array($items) || empty($items)) {
            echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $maxOrder = (int)$pdo2->query("SELECT COALESCE(MAX(sort_order), 0) FROM dept_schedule_members WHERE group_id = $group_id")->fetchColumn();
        $chk = $pdo2->prepare("SELECT id, active FROM dept_schedule_members WHERE group_id = ? AND person_name = ? LIMIT 1");
        $reactivate = $pdo2->prepare("UPDATE dept_schedule_members SET active = 1, phone = COALESCE(NULLIF(?, ''), phone), person_id = COALESCE(?, person_id) WHERE id = ?");
        $ins = $pdo2->prepare("INSERT INTO dept_schedule_members (group_id, person_id, person_name, phone, sort_order) VALUES (?, ?, ?, ?, ?)");

        foreach ($items as $it) {
            $name = trim($it['person_name'] ?? '');
            if ($name === '') continue;
            $pid = !empty($it['person_id']) ? (int)$it['person_id'] : null;
            $phone = trim($it['phone'] ?? '');

            $chk->execute([$group_id, $name]);
            $existing = $chk->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                $reactivate->execute([$phone, $pid, $existing['id']]);
            } else {
                $maxOrder++;
                $ins->execute([$group_id, $pid, $name, $phone, $maxOrder]);
            }
        }

        echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'remove_member') {
        $member_id = (int)($body['member_id'] ?? 0);
        if ($member_id > 0) {
            $stmt = $pdo2->prepare("UPDATE dept_schedule_members SET active = 0 WHERE id = ?");
            $stmt->execute([$member_id]);
        }
        echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'set_cell') {
        $group_id = (int)($body['group_id'] ?? 0);
        $callerAccess = getUserGroupAccessLevel($pdo2, $group_id, $req_user_id, $req_is_admin);
        if (!in_array($callerAccess, ['ADMIN', 'STAFF'])) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์แก้ไขตารางเวรนี้'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $member_id = (int)($body['member_id'] ?? 0);
        $year = (int)($body['year'] ?? 0);
        $month = (int)($body['month'] ?? 0);
        $day = (int)($body['day'] ?? 0);
        $shift_code = trim($body['shift_code'] ?? '');

        if ($group_id <= 0 || $member_id <= 0 || $year <= 0 || $month <= 0 || $day <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($shift_code === '') {
            $del = $pdo2->prepare("
                DELETE FROM dept_schedule_entries
                WHERE group_id = ? AND member_id = ? AND duty_year = ? AND duty_month = ? AND duty_day = ?
            ");
            $del->execute([$group_id, $member_id, $year, $month, $day]);
        } else {
            $up = $pdo2->prepare("
                INSERT INTO dept_schedule_entries (group_id, member_id, duty_year, duty_month, duty_day, shift_code)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE shift_code = VALUES(shift_code)
            ");
            $up->execute([$group_id, $member_id, $year, $month, $day, $shift_code]);
        }

        echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'copy_prev_month') {
        $group_id = (int)($body['group_id'] ?? 0);
        $year = (int)($body['year'] ?? 0);
        $month = (int)($body['month'] ?? 0);

        if ($group_id <= 0 || $year <= 0 || $month <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $prevMonth = $month - 1;
        $prevYear = $year;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }

        $daysInTarget = (int)date('t', mktime(0, 0, 0, $month, 1, $year));

        $stmtPrev = $pdo2->prepare("
            SELECT member_id, duty_day, shift_code
            FROM dept_schedule_entries
            WHERE group_id = ? AND duty_year = ? AND duty_month = ? AND duty_day <= ?
        ");
        $stmtPrev->execute([$group_id, $prevYear, $prevMonth, $daysInTarget]);
        $rows = $stmtPrev->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลเวรของเดือนก่อนหน้า'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $up = $pdo2->prepare("
            INSERT INTO dept_schedule_entries (group_id, member_id, duty_year, duty_month, duty_day, shift_code)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE shift_code = VALUES(shift_code)
        ");
        $copied = 0;
        foreach ($rows as $r) {
            $up->execute([$group_id, $r['member_id'], $year, $month, $r['duty_day'], $r['shift_code']]);
            $copied++;
        }

        echo json_encode(['status' => 'success', 'copied' => $copied], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'send_today_notify') {
        $group_id = (int)($body['group_id'] ?? 0);
        $callerAccess = getUserGroupAccessLevel($pdo2, $group_id, $req_user_id, $req_is_admin, $req_dept_id);
        if (!in_array($callerAccess, ['ADMIN', 'STAFF'])) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์ส่งแจ้งเตือนตารางเวรนี้'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmtG = $pdo2->prepare("SELECT * FROM dept_schedule_groups WHERE id = ? AND active = 1");
        $stmtG->execute([$group_id]);
        $group = $stmtG->fetch(PDO::FETCH_ASSOC);
        if (!$group || empty($group['notify_channel'])) {
            echo json_encode(['status' => 'error', 'message' => 'ยังไม่ได้ตั้งค่า Channel MOPH Notify สำหรับกลุ่มงานนี้'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        require_once __DIR__ . '/../../moph_notify_helper.php';
        require_once __DIR__ . '/notify_helper.php';
        $payload = buildDeptScheduleFlexPayload($pdo2, $group, date('Y-m-d'), false);
        if (!$payload) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบรายการเวรในช่วง 14 วันข้างหน้า'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $res = sendMophNotifyToChannel($pdo2, $group['notify_channel'], $payload);

        if (!empty($res['ok'])) {
            echo json_encode(['status' => 'success', 'message' => 'ส่งแจ้งเตือนเข้า MOPH Notify เรียบร้อยแล้ว'], JSON_UNESCAPED_UNICODE);
        } else {
            $errMsg = $res['curl_error'] ?: ($res['decoded']['message'] ?? "HTTP {$res['http_code']}");
            echo json_encode(['status' => 'error', 'message' => 'ส่งแจ้งเตือนไม่สำเร็จ: ' . $errMsg], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if ($action === 'test_notify') {
        if (!$req_is_admin && $req_user_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์ทดสอบส่งแจ้งเตือน'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $channel_code = trim((string)($body['channel_code'] ?? ''));
        $title = trim((string)($body['title'] ?? 'ตารางเวรกลุ่มงาน'));
        $group_id = (int)($body['group_id'] ?? 0);
        if ($channel_code === '') {
            echo json_encode(['status' => 'error', 'message' => 'กรุณาเลือก Channel MOPH Notify ก่อน'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $groupData = ['id' => $group_id, 'title' => $title, 'department_name' => '', 'shift_types_json' => '[]'];
        if ($group_id > 0) {
            $stG = $pdo2->prepare("SELECT * FROM dept_schedule_groups WHERE id = ? LIMIT 1");
            $stG->execute([$group_id]);
            $found = $stG->fetch(PDO::FETCH_ASSOC);
            if ($found) {
                $groupData = $found;
                if ($title !== '') $groupData['title'] = $title;
            }
        }

        require_once __DIR__ . '/../../moph_notify_helper.php';
        require_once __DIR__ . '/notify_helper.php';
        $payload = buildDeptScheduleFlexPayload($pdo2, $groupData, date('Y-m-d'), true);
        $res = sendMophNotifyToChannel($pdo2, $channel_code, $payload);

        if (!empty($res['ok'])) {
            echo json_encode(['status' => 'success', 'message' => 'ส่ง Flex Message ทดสอบเข้า MOPH Notify เรียบร้อยแล้ว'], JSON_UNESCAPED_UNICODE);
        } else {
            $errMsg = $res['curl_error'] ?: ($res['decoded']['message'] ?? "HTTP {$res['http_code']}");
            echo json_encode(['status' => 'error', 'message' => 'ส่งแจ้งเตือนไม่สำเร็จ: ' . $errMsg], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Unknown action'], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

