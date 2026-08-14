<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require '../config.php';

// Optional: Security check if needed, though frontend handles UI visibility
// $user_id = $_SESSION['user_id'] ?? 0;
// if (!$user_id) ...

try {
    // 1. Identify Requester
    $requester_id = $_SESSION['user_id'] ?? 0;

    // Check Requester Rights & Department
    $is_admin = false;
    $requester_dept_id = 0;

    if ($requester_id) {
        $stmt = $pdo3->prepare("SELECT HR_DEPARTMENT_SUB_ID, access_user FROM hr_person WHERE ID = :id");
        $stmt->bindParam(':id', $requester_id);
        $stmt->execute();
        $requester = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($requester) {
            $requester_dept_id = $requester['HR_DEPARTMENT_SUB_ID'];
            $rights = isset($requester['access_user']) ? explode(':', $requester['access_user']) : [];
            if (in_array('Super', $rights) || in_array('Admin', $rights) || in_array('administrator', $rights)) {
                $is_admin = true;
            }
        }
    }

    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $department_id = isset($_GET['department_id']) ? $_GET['department_id'] : '';

    $whereParams = ["p.HR_STATUS_ID = '01'"];
    $params = [];

    // If NOT Admin, FORCE filter by own department
    if (!$is_admin && $requester_dept_id) {
        $department_id = $requester_dept_id; // Override any requested dept
    }

    // Search Filter
    if (!empty($search)) {
        $whereParams[] = "(p.HR_FNAME LIKE :search1 OR p.HR_LNAME LIKE :search2 OR p.HR_USERNAME LIKE :search3)";
        $params[':search1'] = "%$search%";
        $params[':search2'] = "%$search%";
        $params[':search3'] = "%$search%";
    }

    // Department Filter
    if (!empty($department_id)) {
        $whereParams[] = "p.HR_DEPARTMENT_SUB_ID = :dept_id";
        $params[':dept_id'] = $department_id;
    }

    $whereClause = implode(' AND ', $whereParams);

    $sql = "SELECT 
                p.ID,
                p.FINGLE_ID,
                p.HR_USERNAME,
                p.HR_EMAIL,
                p.HR_PHONE,
                p.NICKNAME,
                p.HR_STARTWORK_DATE,
                p.HR_POSITION_NUM,
                p.VCODE,
                CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as FULLNAME,
                p.access_user,
                CONCAT(COALESCE(pos.HR_POSITION_NAME, '-'), COALESCE(hl.HR_LEVEL_NAME, '')) as POSITION_NAME,
                COALESCE(dep.HR_DEPARTMENT_SUB_NAME, '-') as DEPARTMENT_NAME,
                COALESCE(pt.HR_PERSON_TYPE_NAME, 'ไม่ระบุ') as PERSON_TYPE_NAME
            FROM hr_person p
            LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
            LEFT JOIN hr_position pos ON p.HR_POSITION_ID = pos.HR_POSITION_ID
            LEFT JOIN hr_level hl ON p.HR_LEVEL_ID = hl.HR_LEVEL_ID
            LEFT JOIN hr_department_sub dep ON p.HR_DEPARTMENT_SUB_ID = dep.HR_DEPARTMENT_SUB_ID
            LEFT JOIN hr_person_type pt ON p.HR_PERSON_TYPE_ID = pt.HR_PERSON_TYPE_ID
            WHERE $whereClause
            ORDER BY p.ID ASC";

    $stmt = $pdo3->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "success", "data" => $data]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
