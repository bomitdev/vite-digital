<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';
require_once __DIR__ . '/repair_auth_helper.php';

try {
    $userCtx = getGeneralRepairUserContext($pdo2, $pdo3);
    $canManage = $userCtx['can_manage'];

    $search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
    $status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
    $categoryId = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null;
    $locationId = isset($_GET['location_id']) && $_GET['location_id'] !== '' ? (int)$_GET['location_id'] : null;
    $priorityId = isset($_GET['priority_id']) ? trim((string)$_GET['priority_id']) : '';
    $techName = isset($_GET['technician_name']) ? trim((string)$_GET['technician_name']) : '';
    $requesterName = isset($_GET['requester_name']) ? trim((string)$_GET['requester_name']) : '';
    $myOnly = isset($_GET['my_only']) && in_array(strtolower((string)$_GET['my_only']), ['1', 'true'], true);
    $currentUser = isset($_GET['current_user']) ? trim((string)$_GET['current_user']) : '';
    $dateStart = isset($_GET['date_start']) ? trim((string)$_GET['date_start']) : '';
    $dateEnd = isset($_GET['date_end']) ? trim((string)$_GET['date_end']) : '';

    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(200, max(1, (int)$_GET['limit'])) : 50;
    $offset = ($page - 1) * $limit;

    // Base WHERE
    $whereClauses = ["1=1"];
    $params = [];

    if ($search !== '') {
        $whereClauses[] = "(
            ticket_no LIKE :search 
            OR repair_title LIKE :search 
            OR symptom LIKE :search 
            OR requester_name LIKE :search 
            OR location_name LIKE :search 
            OR category_name LIKE :search 
            OR technician_name LIKE :search
            OR asset_id LIKE :search
        )";
        $params[':search'] = "%{$search}%";
    }

    if ($status !== '') {
        if (strtolower($status) === 'active') {
            $whereClauses[] = "status IN ('Pending', 'In Progress', 'Wait for Part')";
        } else {
            $whereClauses[] = "status = :status";
            $params[':status'] = $status;
        }
    }

    if ($categoryId !== null) {
        $whereClauses[] = "category_id = :cat_id";
        $params[':cat_id'] = $categoryId;
    }

    if ($locationId !== null) {
        $whereClauses[] = "location_id = :loc_id";
        $params[':loc_id'] = $locationId;
    }

    if ($priorityId !== '') {
        $whereClauses[] = "priority_id = :priority_id";
        $params[':priority_id'] = $priorityId;
    }

    if ($techName !== '') {
        $whereClauses[] = "technician_name LIKE :tech_name";
        $params[':tech_name'] = "%{$techName}%";
    }

    if ($myOnly && $currentUser !== '') {
        $whereClauses[] = "(requester_name LIKE :my_user)";
        $params[':my_user'] = "%{$currentUser}%";
    } elseif ($requesterName !== '') {
        $whereClauses[] = "requester_name LIKE :req_name";
        $params[':req_name'] = "%{$requesterName}%";
    }

    if ($dateStart !== '') {
        $whereClauses[] = "created_at >= :date_start";
        $params[':date_start'] = $dateStart . " 00:00:00";
    }

    if ($dateEnd !== '') {
        $whereClauses[] = "created_at <= :date_end";
        $params[':date_end'] = $dateEnd . " 23:59:59";
    }

    // Role-based scoping:
    // If ordinary user (NOT technician AND NOT admin):
    // Strict scoping: can ONLY see requests created by themselves OR their department/workgroup
    $scopeSql = "1=1";
    $scopeParams = [];
    if (!$canManage) {
        $userUid = (int)$userCtx['uid'];
        $userFullname = !empty($userCtx['fullname']) ? $userCtx['fullname'] : $currentUser;
        $userDeptId = !empty($userCtx['department_id']) ? $userCtx['department_id'] : (isset($_GET['department_id']) ? trim((string)$_GET['department_id']) : '');
        $userSubDept = !empty($userCtx['department_sub_name']) ? $userCtx['department_sub_name'] : (isset($_GET['department_name']) ? trim((string)$_GET['department_name']) : '');
        $userMainDept = !empty($userCtx['department_name']) ? $userCtx['department_name'] : '';

        $scopeClauses = [];
        if ($userUid > 0) {
            $scopeClauses[] = "requester_id = :scope_uid";
            $scopeParams[':scope_uid'] = $userUid;
        }
        if ($userFullname !== '') {
            $cleanName = trim(preg_replace('/^(นาย|นางสาว|นาง|นพ\.|พญ\.|ทพ\.|ทพญ\.)\s*/u', '', $userFullname));
            if ($cleanName !== '') {
                $scopeClauses[] = "requester_name LIKE :scope_name";
                $scopeParams[':scope_name'] = "%{$cleanName}%";
            }
        }
        if ($userSubDept !== '') {
            $scopeClauses[] = "department_name LIKE :scope_sub_dept";
            $scopeParams[':scope_sub_dept'] = "%{$userSubDept}%";
        }
        if ($userMainDept !== '' && $userMainDept !== $userSubDept) {
            $scopeClauses[] = "department_name LIKE :scope_main_dept";
            $scopeParams[':scope_main_dept'] = "%{$userMainDept}%";
        }
        if ($userDeptId !== '') {
            $scopeClauses[] = "(department_id IS NOT NULL AND department_id != '' AND department_id = :scope_dept_id)";
            $scopeParams[':scope_dept_id'] = $userDeptId;
        }

        if (!empty($scopeClauses)) {
            $scopeSql = "(" . implode(" OR ", $scopeClauses) . ")";
            $whereClauses[] = $scopeSql;
            $params = array_merge($params, $scopeParams);
        }
    }

    $whereSql = implode(" AND ", $whereClauses);

    // Get total filtered count
    $countStmt = $pdo2->prepare("SELECT COUNT(*) FROM general_repair_requests WHERE {$whereSql}");
    $countStmt->execute($params);
    $totalFiltered = (int)$countStmt->fetchColumn();

    // Query rows
    $sql = "
        SELECT 
            id, ticket_no, legacy_id, repair_title, symptom,
            category_id, category_name, service_type_id, service_type_name,
            location_id, location_name, priority_id, priority_name,
            requester_id, requester_name, requester_phone,
            department_id, department_name, asset_id,
            technician_id, technician_name, technician_comment,
            status, received_at, completed_at, cancel_reason,
            image_before, image_after,
            satisfaction_score, satisfaction_comment,
            created_at, updated_at
        FROM general_repair_requests
        WHERE {$whereSql}
        ORDER BY 
            CASE 
                WHEN status = 'In Progress' THEN 1
                WHEN status = 'Pending' THEN 2
                WHEN status = 'Wait for Part' THEN 3
                ELSE 4
            END ASC,
            priority_id DESC,
            id DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo2->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stats counters (scoped for non-technicians, global for technicians/admins)
    $statsSql = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'Wait for Part' THEN 1 ELSE 0 END) as wait_part,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled,
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_new,
            SUM(CASE WHEN DATE(completed_at) = CURDATE() THEN 1 ELSE 0 END) as today_completed
        FROM general_repair_requests
        WHERE {$scopeSql}
    ";
    $statsStmt = $pdo2->prepare($statsSql);
    $statsStmt->execute($scopeParams);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $requests,
        'pagination' => [
            'total' => $totalFiltered,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($totalFiltered / $limit),
        ],
        'stats' => $stats,
        'user_permission' => [
            'is_admin' => $userCtx['is_admin'],
            'is_technician' => $userCtx['is_technician'],
            'can_manage' => $userCtx['can_manage'],
            'department_name' => $userCtx['department_sub_name'] ?: $userCtx['department_name'],
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

