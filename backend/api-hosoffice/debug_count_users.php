<?php
require '../config.php';

try {
    // 1. Count all users with status '01'
    $sql = "SELECT COUNT(*) as total FROM hr_person WHERE HR_STATUS_ID = '01'";
    $stmt = $pdo3->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total Active Users (HR_STATUS_ID = '01'): " . $result['total'] . "\n";

    // 2. Check for distinct status IDs just in case
    $sql = "SELECT DISTINCT HR_STATUS_ID FROM hr_person";
    $stmt_status = $pdo3->prepare($sql);
    $stmt_status->execute();
    $statuses = $stmt_status->fetchAll(PDO::FETCH_ASSOC);
    echo "Existing Status IDs: ";
    foreach ($statuses as $s) {
        echo "'" . $s['HR_STATUS_ID'] . "' ";
    }
    echo "\n";

    // 3. Check specific query used in get_authorized_staff (simulated for Admin)
    $where_sql = "p.HR_STATUS_ID = '01'";
    $sql_full = "SELECT 
                    COUNT(*) as valid_count,
                    SUM(CASE WHEN CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) IS NULL THEN 1 ELSE 0 END) as null_names
            FROM hr_person p
            LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
            WHERE $where_sql";


    $stmt_full = $pdo3->prepare($sql_full);
    $stmt_full->execute();
    $res_full = $stmt_full->fetch(PDO::FETCH_ASSOC);
    echo "Count from API query logic: " . $res_full['valid_count'] . "\n";
    echo "Rows with NULL Fullname: " . $res_full['null_names'] . "\n";

    // 4. Count by Status
    $sql_status = "SELECT HR_STATUS_ID, COUNT(*) as c FROM hr_person GROUP BY HR_STATUS_ID";
    $stmt_status = $pdo3->prepare($sql_status);
    $stmt_status->execute();
    $rows = $stmt_status->fetchAll(PDO::FETCH_ASSOC);
    echo "Counts by Status ID:\n";
    foreach ($rows as $r) {
        echo "ID: " . $r['HR_STATUS_ID'] . " = " . $r['c'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
