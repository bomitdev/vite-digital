<?php
/**
 * Central Audit Logs API (Admin Read-Only + Authenticated Event Recording)
 * Append-only: DELETE and UPDATE are intentionally unsupported.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../auth_utils.php';
require_once __DIR__ . '/../audit_log_helper.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

ensureSysAuditLogsTable($pdo2);

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $userData = authGuard();
        $authUid = (int)($userData['uid'] ?? 0);

        $isAdmin = false;
        if (isset($pdo3) && $authUid > 0) {
            $stAdm = $pdo3->prepare("SELECT access_user FROM hr_person WHERE ID = ? LIMIT 1");
            $stAdm->execute([$authUid]);
            $accStr = (string)$stAdm->fetchColumn();
            $perms = array_filter(array_map('trim', explode(':', $accStr)));
            $isAdmin = in_array('administrator', $perms, true);
        }

        if (!$isAdmin) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'เฉพาะผู้ดูแลระบบ (Admin) เท่านั้นที่สามารถดู Audit Log ได้'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $search = trim((string)($_GET['search'] ?? ''));
        $module = trim((string)($_GET['module'] ?? ''));
        $action = trim((string)($_GET['action'] ?? ''));
        $dateFrom = trim((string)($_GET['date_from'] ?? ''));
        $dateTo = trim((string)($_GET['date_to'] ?? ''));
        $limit = max(1, min(500, (int)($_GET['limit'] ?? 100)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $where = ["1=1"];
        $params = [];

        if ($module !== '' && $module !== 'all') {
            $where[] = "module = ?";
            $params[] = $module;
        }
        if ($action !== '' && $action !== 'all') {
            $where[] = "action = ?";
            $params[] = $action;
        }
        if ($dateFrom !== '') {
            $where[] = "created_at >= ?";
            $params[] = $dateFrom . ' 00:00:00';
        }
        if ($dateTo !== '') {
            $where[] = "created_at <= ?";
            $params[] = $dateTo . ' 23:59:59';
        }
        if ($search !== '') {
            $where[] = "(username LIKE ? OR fullname LIKE ? OR details LIKE ? OR endpoint LIKE ? OR target_id LIKE ? OR ip_address LIKE ?)";
            $q = "%{$search}%";
            array_push($params, $q, $q, $q, $q, $q, $q);
        }

        $whereSql = implode(' AND ', $where);

        $stCount = $pdo2->prepare("SELECT COUNT(*) FROM sys_audit_logs WHERE {$whereSql}");
        $stCount->execute($params);
        $total = (int)$stCount->fetchColumn();

        $stRows = $pdo2->prepare("
            SELECT id, user_id, username, fullname, module, action, method, endpoint,
                   target_type, target_id, status_code, details, meta_json, ip_address, user_agent, created_at
            FROM sys_audit_logs
            WHERE {$whereSql}
            ORDER BY id DESC
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stRows->execute($params);
        $rows = $stRows->fetchAll(PDO::FETCH_ASSOC);

        $stStats = $pdo2->query("
            SELECT
                COUNT(*) AS total_rows,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS today_count,
                SUM(CASE WHEN DATE(created_at) = CURDATE() AND action LIKE '%FAIL%' THEN 1 ELSE 0 END) AS today_fail_count,
                MIN(created_at) AS oldest_log_at
            FROM sys_audit_logs
        ");
        $stats = $stStats->fetch(PDO::FETCH_ASSOC) ?: [];

        $modules = $pdo2->query("SELECT DISTINCT module FROM sys_audit_logs ORDER BY module ASC")->fetchAll(PDO::FETCH_COLUMN);
        $actions = $pdo2->query("SELECT DISTINCT action FROM sys_audit_logs ORDER BY action ASC")->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode([
            'status' => 'success',
            'data' => $rows,
            'total' => $total,
            'stats' => $stats,
            'modules' => $modules,
            'actions' => $actions,
        ], JSON_UNESCAPED_UNICODE);
    } elseif ($method === 'POST') {
        $userData = authGuard();
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || empty($input['action'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Action is required.']);
            exit;
        }

        recordAuditLog($pdo2, [
            'user_id' => (int)($userData['uid'] ?? 0),
            'username' => $userData['user'] ?? null,
            'fullname' => $userData['name'] ?? null,
            'module' => $input['module'] ?? 'frontend',
            'action' => $input['action'],
            'method' => 'POST',
            'endpoint' => $input['endpoint'] ?? ($_SERVER['HTTP_REFERER'] ?? ''),
            'target_type' => $input['target_type'] ?? null,
            'target_id' => $input['target_id'] ?? null,
            'status_code' => 200,
            'details' => $input['details'] ?? null,
            'meta' => $input['meta'] ?? null,
        ], $pdo3 ?? null);

        echo json_encode(['status' => 'success']);
    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed (audit logs are append-only).']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

