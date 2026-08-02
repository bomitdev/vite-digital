<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require '../config.php';

// Authentication Logic (Session or Bearer Token)
require_once __DIR__ . '/../auth_utils.php';

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    // Fallback to Bearer Token parsing
    $userData = authOptional();
    if ($userData) {
        if (isset($userData['uid'])) {
            $user_id = $userData['uid'];
        } elseif (isset($userData['data']['id'])) {
            $user_id = $userData['data']['id'];
        }
    }
}

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->year) || !isset($data->month)) {
    echo json_encode(['status' => 'error', 'message' => 'Month and Year are required']);
    exit();
}

$year = $data->year;
$month = $data->month;

try {
    // 1. Calculate Date Range
    $date_start = sprintf('%04d-%02d-01', $year, $month);
    $date_end = date('Y-m-d', strtotime('+1 month', strtotime($date_start)));

    $pdo3->beginTransaction();

    // 2. นำข้อมูล attendance_logs เข้า hos_fingerscan
    $sqlSyncLogs = "INSERT INTO 10985_hos_fingerscan ( fingerscan_datetime, fingerscan_user_id ) 
    SELECT
        a.`timestamp` AS fingerscan_datetime,
        TRIM( a.user_id ) AS fingerscan_user_id 
    FROM
        10985_attendance_logs a 
    WHERE
        a.`timestamp` >= :date_start 
        AND a.`timestamp` < :date_end 
        AND a.user_id IS NOT NULL 
        AND TRIM( a.user_id ) <> '' 
        AND a.STATUS IN ( 0, 1 ) 
        AND NOT EXISTS (
        SELECT
            1 
        FROM
            10985_hos_fingerscan f 
        WHERE
            TRIM( f.fingerscan_user_id ) = TRIM( a.user_id ) 
            AND f.fingerscan_datetime = a.`timestamp` 
        )";
    
    $stmtSync = $pdo3->prepare($sqlSyncLogs);
    $stmtSync->bindParam(':date_start', $date_start);
    $stmtSync->bindParam(':date_end', $date_end);
    $stmtSync->execute();

    // 3. ลบข้อมูลประมวลผลเดิม
    $sqlDelete = "DELETE FROM 10985_hos_fingerscan_check WHERE gdate >= :date_start AND gdate < :date_end";
    $stmtDelete = $pdo3->prepare($sqlDelete);
    $stmtDelete->bindParam(':date_start', $date_start);
    $stmtDelete->bindParam(':date_end', $date_end);
    $stmtDelete->execute();

    // 4. ประมวลผลเข้า hos_fingerscan_check
    $sqlInsert = "INSERT INTO 10985_hos_fingerscan_check ( gdate, fingerscan_user_id, mor, aft, ot, gstatus ) 
    SELECT
        x.finger_date AS gdate,
        x.fingerscan_user_id,
        x.mor,
        x.aft,
        x.ot,
        CASE
            WHEN x.mor IS NULL AND ( x.aft IS NOT NULL OR x.ot IS NOT NULL ) THEN 2 
            WHEN x.mor >= '08:16:00' THEN 1 
            ELSE 0 
        END AS gstatus 
    FROM
        (
        SELECT
            DATE( f.fingerscan_datetime ) AS finger_date,
            TRIM( f.fingerscan_user_id ) AS fingerscan_user_id,
            MIN( CASE WHEN TIME( f.fingerscan_datetime ) BETWEEN '03:00:00' AND '12:59:59' THEN TIME( f.fingerscan_datetime ) END ) AS mor,
            MIN( CASE WHEN TIME( f.fingerscan_datetime ) BETWEEN '13:00:00' AND '19:59:59' THEN TIME( f.fingerscan_datetime ) END ) AS aft,
            MIN( CASE WHEN TIME( f.fingerscan_datetime ) BETWEEN '20:00:00' AND '23:59:59' THEN TIME( f.fingerscan_datetime ) END ) AS ot 
        FROM
            10985_hos_fingerscan f
            INNER JOIN hr_person u ON TRIM(CAST( u.FINGLE_ID AS CHAR )) = TRIM(CAST( f.fingerscan_user_id AS CHAR )) 
        WHERE
            f.fingerscan_datetime >= :date_start 
            AND f.fingerscan_datetime < :date_end 
            AND u.HR_STATUS_ID = '01' 
            AND CONCAT(':', u.access_user, ':') LIKE '%:user_wage:%'
        GROUP BY
            DATE( f.fingerscan_datetime ),
            TRIM( f.fingerscan_user_id ) 
    ) x";

    $stmtInsert = $pdo3->prepare($sqlInsert);
    $stmtInsert->bindParam(':date_start', $date_start);
    $stmtInsert->bindParam(':date_end', $date_end);
    $stmtInsert->execute();

    $pdo3->commit();

    echo json_encode(['status' => 'success', 'message' => 'Data processed successfully']);
} catch (PDOException $e) {
    if ($pdo3->inTransaction()) {
        $pdo3->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
} catch (Exception $e) {
    if (isset($pdo3) && $pdo3->inTransaction()) {
        $pdo3->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
