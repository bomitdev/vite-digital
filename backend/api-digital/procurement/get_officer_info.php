<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config.php';

try {
    $name = isset($_GET['name']) ? trim($_GET['name']) : '';
    
    if (empty($name)) {
        throw new Exception("Name is required");
    }

    // 1. Find the officer and their department
    $sqlOfficer = "
        SELECT 
            p.ID,
            CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as FULLNAME,
            pos.POSITION_NAME,
            p.HR_DEPARTMENT_SUB_ID
        FROM hr_person p
        LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
        LEFT JOIN hr_position pos ON p.HR_POSITION_ID = pos.HR_POSITION_ID
        WHERE CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) LIKE :name
           OR CONCAT(p.HR_FNAME, ' ', p.HR_LNAME) LIKE :name
        LIMIT 1
    ";
    
    $stmt1 = $pdo3->prepare($sqlOfficer);
    $stmt1->execute([':name' => '%' . str_replace(' ', '%', $name) . '%']);
    $officer = $stmt1->fetch(PDO::FETCH_ASSOC);

    if (!$officer) {
        echo json_encode(['status' => 'error', 'message' => 'Officer not found']);
        exit;
    }

    // 2. Find the leader of their sub department
    $chief = null;
    if ($officer['HR_DEPARTMENT_SUB_ID']) {
        $sqlLeader = "
            SELECT 
                CONCAT(pf.HR_PREFIX_NAME, lp.HR_FNAME, ' ', lp.HR_LNAME) as FULLNAME,
                pos.POSITION_NAME
            FROM hr_department_sub sub
            JOIN hr_person lp ON sub.LEADER_HR_ID = lp.ID
            LEFT JOIN hr_prefix pf ON lp.HR_PREFIX_ID = pf.HR_PREFIX_ID
            LEFT JOIN hr_position pos ON lp.HR_POSITION_ID = pos.HR_POSITION_ID
            WHERE sub.HR_DEPARTMENT_SUB_ID = :dept_id
        ";
        $stmt2 = $pdo3->prepare($sqlLeader);
        $stmt2->execute([':dept_id' => $officer['HR_DEPARTMENT_SUB_ID']]);
        $chief = $stmt2->fetch(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'officer_name' => $officer['FULLNAME'],
            'officer_position' => $officer['POSITION_NAME'],
            'chief_officer_name' => $chief ? $chief['FULLNAME'] : '',
            'chief_officer_position' => $chief ? $chief['POSITION_NAME'] : ''
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
