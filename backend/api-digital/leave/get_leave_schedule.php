<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config.php';

try {
    $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
    $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
    
    // Default to sub departments 14 (Insurance/Strategy) and 27 (Digital Health)
    $dept_ids = isset($_GET['dept_id']) ? $_GET['dept_id'] : '14,27';
    if (!preg_match('/^[0-9,]+$/', $dept_ids)) {
        $dept_ids = '14,27';
    }

    $events = [];

    // 1. Fetch Leaves
    $sqlLeave = "
        SELECT 
            'LEAVE' as event_type,
            lr.ID as event_id,
            lr.LEAVE_PERSON_ID as person_id,
            CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as person_name,
            lr.LEAVE_DATE_BEGIN as start_date,
            lr.LEAVE_DATE_END as end_date,
            COALESCE(lt.LEAVE_TYPE_NAME, lr.LEAVE_TYPE_CODE) as title,
            lr.LEAVE_BECAUSE as detail,
            lr.LEAVE_STATUS_CODE as status_id,
            NULL as attendees
        FROM leave_register lr
        LEFT JOIN leave_type lt ON lr.LEAVE_TYPE_CODE = lt.LEAVE_TYPE_ID
        JOIN hr_person p ON lr.LEAVE_PERSON_ID = p.ID
        LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
        WHERE p.HR_DEPARTMENT_SUB_ID IN ($dept_ids)
        AND lr.LEAVE_CANCEL_STATUS = 'False'
        AND (
            (YEAR(lr.LEAVE_DATE_BEGIN) = :year AND MONTH(lr.LEAVE_DATE_BEGIN) = :month)
            OR (YEAR(lr.LEAVE_DATE_END) = :year_end AND MONTH(lr.LEAVE_DATE_END) = :month_end)
        )
    ";
    $stmt1 = $pdo3->prepare($sqlLeave);
    $stmt1->execute([
        ':year' => $year,
        ':month' => $month,
        ':year_end' => $year,
        ':month_end' => $month
    ]);
    $leaves = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    // 2. Fetch Business Trips
    $sqlTrip = "
        SELECT 
            'TRIP' as event_type,
            ri.ID as event_id,
            ri.HR_ID as person_id,
            CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as person_name,
            ri.DATE_GO as start_date,
            ri.DATE_BACK as end_date,
            ri.RECORD_HEAD_USE as title,
            ri.PROVINCE_NAME as detail,
            ri.STATUS as status_id,
            (SELECT GROUP_CONCAT(HR_FULLNAME SEPARATOR ', ') FROM record_index_person WHERE RECORD_ID = ri.ID) as attendees
        FROM record_index ri
        JOIN hr_person p ON ri.HR_ID = p.ID
        LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
        WHERE p.HR_DEPARTMENT_SUB_ID IN ($dept_ids)
        AND (ri.CANCEL_STATUS IS NULL OR ri.CANCEL_STATUS = '' OR ri.CANCEL_STATUS = 'False')
        AND (
            (YEAR(ri.DATE_GO) = :year AND MONTH(ri.DATE_GO) = :month)
            OR (YEAR(ri.DATE_BACK) = :year_end AND MONTH(ri.DATE_BACK) = :month_end)
        )
    ";
    $stmt2 = $pdo3->prepare($sqlTrip);
    $stmt2->execute([
        ':year' => $year,
        ':month' => $month,
        ':year_end' => $year,
        ':month_end' => $month
    ]);
    $trips = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $events = array_merge($leaves, $trips);

    echo json_encode([
        'status' => 'success',
        'data' => $events
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error fetching schedule: ' . $e->getMessage()
    ]);
}
