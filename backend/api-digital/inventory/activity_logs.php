<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';
require_once __DIR__ . '/../../auth_utils.php';
require_once __DIR__ . '/inv_log_helper.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($pdo2)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

ensureInvActivityLogsTable($pdo2);

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'POST') {
        $input = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = $_POST;
        }

        $action = trim((string)($input['action'] ?? 'VIEW_PAGE'));
        $pageCode = trim((string)($input['page_code'] ?? 'overview'));
        $pageName = trim((string)($input['page_name'] ?? $pageCode));
        $warehouseId = isset($input['warehouse_id']) && $input['warehouse_id'] !== '' ? (int)$input['warehouse_id'] : null;
        $details = trim((string)($input['details'] ?? ''));

        if ($details === '') {
            $details = "เข้าใช้งานหน้า {$pageName}";
        }

        recordInvActivityLog($pdo2, [
            'warehouse_id' => $warehouseId,
            'warehouse_name' => $input['warehouse_name'] ?? null,
            'user_id' => isset($input['user_id']) ? (int)$input['user_id'] : null,
            'user_fullname' => $input['user_name'] ?? null,
            'page_code' => $pageCode,
            'page_name' => $pageName,
            'action' => $action,
            'target_type' => $input['target_type'] ?? 'page',
            'target_id' => $input['target_id'] ?? null,
            'details' => $details,
            'meta' => $input['meta'] ?? null,
        ], $pdo3 ?? null);

        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($method === 'GET') {
        $userData = authGuard();
        $uid = (int)($userData['uid'] ?? 0);

        $isAdmin = false;
        $allowedWarehouses = [];
        if (isset($pdo3) && $uid > 0) {
            $stU = $pdo3->prepare("SELECT access_user FROM hr_person WHERE ID = ? LIMIT 1");
            $stU->execute([$uid]);
            $acc = (string)$stU->fetchColumn();
            $perms = array_filter(array_map('trim', explode(':', $acc)));
            $isAdmin = in_array('administrator', $perms, true)
                || in_array('menu_inventory_manage', $perms, true)
                || in_array('menu_inventory_admin', $perms, true);
        }
        if (!$isAdmin && $uid > 0) {
            $stWh = $pdo2->prepare("SELECT warehouse_id FROM inv_user_warehouses WHERE user_id = ?");
            $stWh->execute([$uid]);
            $allowedWarehouses = array_map('intval', $stWh->fetchAll(PDO::FETCH_COLUMN));
            if (empty($allowedWarehouses)) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์ดูประวัติการใช้งานระบบคลัง'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        $warehouseId = isset($_GET['warehouse_id']) && $_GET['warehouse_id'] !== '' ? (int)$_GET['warehouse_id'] : null;
        $pageCode = trim((string)($_GET['page_code'] ?? ''));
        $actionFilter = trim((string)($_GET['action'] ?? ''));
        $category = trim((string)($_GET['category'] ?? 'all')); // all | write | view
        $search = trim((string)($_GET['search'] ?? ''));
        $startDate = trim((string)($_GET['start_date'] ?? ''));
        $endDate = trim((string)($_GET['end_date'] ?? ''));
        $limit = isset($_GET['limit']) ? max(20, min(500, (int)$_GET['limit'])) : 150;

        $where = ['1=1'];
        $params = [];

        if (!$isAdmin && !empty($allowedWarehouses)) {
            $inClause = implode(',', $allowedWarehouses);
            $where[] = "(warehouse_id IN ({$inClause}) OR warehouse_id IS NULL)";
        }
        if ($warehouseId) {
            $where[] = 'warehouse_id = ?';
            $params[] = $warehouseId;
        }
        if ($pageCode !== '') {
            $where[] = 'page_code = ?';
            $params[] = $pageCode;
        }
        if ($actionFilter !== '') {
            $where[] = 'action = ?';
            $params[] = $actionFilter;
        } elseif ($category === 'view') {
            $where[] = "action IN ('VIEW_PAGE', 'SWITCH_WAREHOUSE', 'VIEW_STOCK_CARD')";
        } elseif ($category === 'write') {
            $where[] = "action NOT IN ('VIEW_PAGE', 'SWITCH_WAREHOUSE', 'VIEW_STOCK_CARD')";
        }
        if ($startDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $where[] = 'created_at >= ?';
            $params[] = $startDate . ' 00:00:00';
        }
        if ($endDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            $where[] = 'created_at <= ?';
            $params[] = $endDate . ' 23:59:59';
        }
        if ($search !== '') {
            $where[] = '(user_fullname LIKE ? OR username LIKE ? OR details LIKE ? OR target_id LIKE ? OR page_name LIKE ? OR ip_address LIKE ?)';
            $like = "%{$search}%";
            array_push($params, $like, $like, $like, $like, $like, $like);
        }

        $whereSql = implode(' AND ', $where);
        $sql = "SELECT * FROM inv_activity_logs WHERE {$whereSql} ORDER BY id DESC LIMIT {$limit}";
        $st = $pdo2->prepare($sql);
        $st->execute($params);
        $logs = $st->fetchAll(PDO::FETCH_ASSOC);

        $todayScope = "DATE(created_at) = CURDATE()";
        $whStatFilter = $warehouseId ? " AND warehouse_id = " . (int)$warehouseId : "";
        if (!$isAdmin && !empty($allowedWarehouses)) {
            $inClause = implode(',', $allowedWarehouses);
            $whStatFilter .= " AND (warehouse_id IN ({$inClause}) OR warehouse_id IS NULL)";
        }

        $stats = $pdo2->query("
            SELECT
                COUNT(*) AS total_today,
                SUM(CASE WHEN action IN ('VIEW_PAGE', 'SWITCH_WAREHOUSE', 'VIEW_STOCK_CARD') THEN 1 ELSE 0 END) AS views_today,
                SUM(CASE WHEN action NOT IN ('VIEW_PAGE', 'SWITCH_WAREHOUSE', 'VIEW_STOCK_CARD') THEN 1 ELSE 0 END) AS writes_today,
                COUNT(DISTINCT user_fullname) AS distinct_users_today
            FROM inv_activity_logs
            WHERE {$todayScope} {$whStatFilter}
        ")->fetch(PDO::FETCH_ASSOC);

        $pages = $pdo2->query("SELECT DISTINCT page_code, COALESCE(page_name, page_code) AS page_name FROM inv_activity_logs ORDER BY page_code ASC")->fetchAll(PDO::FETCH_ASSOC);
        $actions = $pdo2->query("SELECT DISTINCT action FROM inv_activity_logs ORDER BY action ASC")->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode([
            'status' => 'success',
            'summary' => [
                'total_today' => (int)($stats['total_today'] ?? 0),
                'views_today' => (int)($stats['views_today'] ?? 0),
                'writes_today' => (int)($stats['writes_today'] ?? 0),
                'distinct_users_today' => (int)($stats['distinct_users_today'] ?? 0),
            ],
            'filters' => [
                'pages' => $pages,
                'actions' => $actions,
            ],
            'data' => $logs,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

