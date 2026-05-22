<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth_utils.php';

/** @var PDO $pdo3 */

// Secure Auth
$userData = authGuard();
$user_id = $userData['uid'];


try {
    // 1. Get Requester Info
    $stmt = $pdo3->prepare("
        SELECT p.HR_DEPARTMENT_SUB_ID, p.access_user, d.HR_DEPARTMENT_SUB_NAME 
        FROM hr_person p 
        LEFT JOIN hr_department_sub d ON p.HR_DEPARTMENT_SUB_ID = d.HR_DEPARTMENT_SUB_ID 
        WHERE p.ID = :id
    ");
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    $requester = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$requester) {
        echo json_encode(["status" => "error", "message" => "User not found"]);
        exit;
    }

    $dept_id = $requester['HR_DEPARTMENT_SUB_ID'];
    $dept_name = $requester['HR_DEPARTMENT_SUB_NAME'] ?? '';
    $rights = isset($requester['access_user']) ? explode(':', $requester['access_user']) : [];

    // 2. Determine Permission Level
    $view_all = false;
    $view_dept_plus_type06_pos24 = false; // fingerscan_print_all
    $view_dept_plus_pos_na = false;       // fingerscan_user_all
    $view_own_dept_only = false;          // New logic for specific departments

    if (in_array('administrator', $rights) || in_array('Admin', $rights) || in_array('Super', $rights)) {
        $view_all = true;
    }

    if (in_array('fingerscan_print_all', $rights)) {
        $view_dept_plus_type06_pos24 = true;
    }

    if (in_array('fingerscan_user_all', $rights)) {
        $view_dept_plus_pos_na = true;
    }

    // Check specific allowed departments
    $allowed_keywords = ['สุขภาพดิจิทัล', 'ประกันสุขภาพ', 'ยุทธศาสตร์'];
    foreach ($allowed_keywords as $kw) {
        if (strpos($dept_name, $kw) !== false) {
            $view_own_dept_only = true;
            break;
        }
    }

    if (!$view_all && !$view_dept_plus_type06_pos24 && !$view_dept_plus_pos_na && !$view_own_dept_only) {
        // No special rights, return empty
        echo json_encode(["status" => "success", "data" => []]);
        exit;
    }

    // 3. Build Query
    // Base: Active users only
    $where_conditions = ["p.HR_STATUS_ID = '01'"];
    $params = [];

    // Logic Construction
    if ($view_all) {
        // No extra filters needed, just all active users
    } else {
        $role_conditions = [];

        // Allow viewing own department if flagged
        if ($view_own_dept_only && $dept_id) {
            $role_conditions[] = "p.HR_DEPARTMENT_SUB_ID = :my_dept_id";
            $params[':my_dept_id'] = $dept_id;
        }

        // Condition for fingerscan_print_all
        if ($view_dept_plus_type06_pos24) {
            $sub_cond = [];
            if ($dept_id) {
                // Use a separate param name to avoid conflict if both blocks run
                $sub_cond[] = "p.HR_DEPARTMENT_SUB_ID = :dept_id_1";
                $params[':dept_id_1'] = $dept_id;
            }
            $sub_cond[] = "p.HR_PERSON_TYPE_ID = '06'";
            $sub_cond[] = "p.HR_POSITION_ID = '24'";

            $role_conditions[] = "(" . implode(" OR ", $sub_cond) . ")";
        }

        // Condition for fingerscan_user_all
        if ($view_dept_plus_pos_na) {
            $sub_cond = [];
            if ($dept_id) {
                $sub_cond[] = "p.HR_DEPARTMENT_SUB_ID = :dept_id_2";
                $params[':dept_id_2'] = $dept_id;
            }
            $sub_cond[] = "p.HR_POSITION_ID IN ('66', '69', '71', '67')";

            $role_conditions[] = "(" . implode(" OR ", $sub_cond) . ")";
        }

        if (!empty($role_conditions)) {
            $where_conditions[] = "(" . implode(" OR ", $role_conditions) . ")";
        } else {
            // Should not happen given the checks above, but failsafe
            $where_conditions[] = "1=0";
        }
    }

    $where_sql = implode(' AND ', $where_conditions);

    $sql = "SELECT 
                p.ID,
                CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as FULLNAME,
                p.HR_DEPARTMENT_SUB_ID,
                p.HR_PERSON_TYPE_ID,
                p.HR_POSITION_ID,
                p.access_user,
                pos.HR_POSITION_NAME,
                hl.HR_LEVEL_NAME,
                hds.HR_DEPARTMENT_SUB_NAME
            FROM hr_person p
            LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
            LEFT JOIN hr_position pos ON p.HR_POSITION_ID = pos.HR_POSITION_ID
            LEFT JOIN hr_level hl ON p.HR_LEVEL_ID = hl.HR_LEVEL_ID
            LEFT JOIN hr_department_sub hds ON p.HR_DEPARTMENT_SUB_ID = hds.HR_DEPARTMENT_SUB_ID
            WHERE $where_sql
            ORDER BY p.HR_FNAME ASC";

    $stmt = $pdo3->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $data]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
