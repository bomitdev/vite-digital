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
    // 1. Check Permission (Optional but recommended)
    // Assuming simple check for now or relying on frontend verification. 
    // Ideally check if user has 'admin' or 'manage_schedule' rights.

    // 2. Execute Logic
    // DELETE
    $sqlDelete = "DELETE FROM 10985_hos_fingerscan_check WHERE YEAR(gdate) = :y AND MONTH(gdate) = :m";
    $stmtDelete = $pdo3->prepare($sqlDelete);
    $stmtDelete->bindParam(':y', $year, PDO::PARAM_INT);
    $stmtDelete->bindParam(':m', $month, PDO::PARAM_INT);
    $stmtDelete->execute();

    // INSERT
    // Note: The original query used @vars. Using placeholders is safer/cleaner in PDO.
    $sqlInsert = "INSERT INTO 10985_hos_fingerscan_check (gdate, fingerscan_user_id, mor, aft, ot, gstatus)
    SELECT
      x.finger_date AS gdate,
      x.fingerscan_user_id,
      x.mor,
      x.aft,
      x.ot,
      CASE
        WHEN x.mor IS NULL AND (x.aft IS NOT NULL OR x.ot IS NOT NULL) THEN 2
        WHEN x.mor >= '08:16:00' THEN 1
        ELSE 0
      END AS gstatus
    FROM (
      SELECT
        DATE(f.fingerscan_datetime) AS finger_date,
        f.fingerscan_user_id,

        MIN(CASE
              WHEN TIME(f.fingerscan_datetime) BETWEEN '03:00:00' AND '12:59:59'
              THEN TIME(f.fingerscan_datetime)
            END) AS mor,

        MIN(CASE
              WHEN TIME(f.fingerscan_datetime) BETWEEN '13:00:00' AND '19:59:59'
              THEN TIME(f.fingerscan_datetime)
            END) AS aft,

        MIN(CASE
              WHEN TIME(f.fingerscan_datetime) BETWEEN '20:00:00' AND '23:59:59'
              THEN TIME(f.fingerscan_datetime)
            END) AS ot
      FROM 10985_hos_fingerscan f
      JOIN hr_person u
        ON u.FINGLE_ID = f.fingerscan_user_id
       AND CONCAT(':', u.access_user, ':') LIKE '%:user_wage:%'
       AND u.HR_STATUS_ID = '01'
      WHERE YEAR(f.fingerscan_datetime) = :y
        AND MONTH(f.fingerscan_datetime) = :m
      GROUP BY DATE(f.fingerscan_datetime), f.fingerscan_user_id
    ) x";

    $stmtInsert = $pdo3->prepare($sqlInsert);
    $stmtInsert->bindParam(':y', $year, PDO::PARAM_INT);
    $stmtInsert->bindParam(':m', $month, PDO::PARAM_INT);
    $stmtInsert->execute();

    echo json_encode(['status' => 'success', 'message' => 'Data processed successfully']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
