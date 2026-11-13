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

    // Locations
    $stmtLoc = $pdo2->query("SELECT id, name FROM general_repair_locations WHERE is_active = 1 ORDER BY name ASC");
    $locations = $stmtLoc->fetchAll(PDO::FETCH_ASSOC);

    // Categories
    $stmtCat = $pdo2->query("SELECT id, name FROM general_repair_categories WHERE is_active = 1 ORDER BY name ASC");
    $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

    // Service Types
    $stmtServ = $pdo2->query("SELECT id, name FROM general_repair_service_types WHERE is_active = 1 ORDER BY id ASC");
    $serviceTypes = $stmtServ->fetchAll(PDO::FETCH_ASSOC);

    // Technicians (from general_repair_technicians)
    $stmtTech = $pdo2->query("SELECT id, person_id, fullname, position, phone FROM general_repair_technicians WHERE is_active = 1 ORDER BY fullname ASC");
    $technicians = $stmtTech->fetchAll(PDO::FETCH_ASSOC);

    // Also get all active staff from hosoffice if needed for requester dropdown / technician assignment
    $stmtStaff = $pdo3->query("
        SELECT p.ID, CONCAT(COALESCE(p.HR_FNAME, ''), ' ', COALESCE(p.HR_LNAME, '')) AS FULLNAME,
               COALESCE(hds.HR_DEPARTMENT_SUB_NAME, '') AS DEPARTMENT,
               p.HR_PHONE AS PHONE
        FROM hr_person p
        LEFT JOIN hr_department_sub hds ON p.HR_DEPARTMENT_SUB_ID = hds.HR_DEPARTMENT_SUB_ID
        WHERE p.HR_STATUS_ID = 1
        ORDER BY p.HR_FNAME ASC
    ");
    $staffList = $stmtStaff->fetchAll(PDO::FETCH_ASSOC);

    $priorities = [
        ['id' => '01', 'name' => 'ปกติ', 'badge' => 'bg-secondary', 'level' => 1],
        ['id' => '02', 'name' => 'ด่วน', 'badge' => 'bg-warning text-dark', 'level' => 2],
        ['id' => '03', 'name' => 'ด่วนมาก', 'badge' => 'bg-orange text-white', 'level' => 3],
        ['id' => '04', 'name' => 'ด่วนที่สุด', 'badge' => 'bg-danger text-white', 'level' => 4],
    ];

    $statuses = [
        ['code' => 'Pending', 'name' => 'รอดำเนินการ', 'badge' => 'bg-warning text-dark'],
        ['code' => 'In Progress', 'name' => 'กำลังดำเนินการ', 'badge' => 'bg-primary'],
        ['code' => 'Wait for Part', 'name' => 'รออะไหล่/พักงาน', 'badge' => 'bg-info text-dark'],
        ['code' => 'Completed', 'name' => 'เสร็จสิ้น', 'badge' => 'bg-success'],
        ['code' => 'Cancelled', 'name' => 'ยกเลิก', 'badge' => 'bg-danger'],
    ];

    echo json_encode([
        'status' => 'success',
        'data' => [
            'locations' => $locations,
            'categories' => $categories,
            'service_types' => $serviceTypes,
            'technicians' => $technicians,
            'staff_list' => $staffList,
            'priorities' => $priorities,
            'statuses' => $statuses,
            'user_permission' => [
                'uid' => $userCtx['uid'],
                'is_admin' => $userCtx['is_admin'],
                'is_technician' => $userCtx['is_technician'],
                'can_manage' => $userCtx['can_manage'],
                'fullname' => $userCtx['fullname'],
                'department_id' => $userCtx['department_id'],
                'department_name' => $userCtx['department_name'],
                'department_sub_id' => $userCtx['department_sub_id'],
                'department_sub_name' => $userCtx['department_sub_name'],
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

