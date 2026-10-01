<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require __DIR__ . '/../../config.php';

try {
    $data = json_decode(file_get_contents("php://input"));
    $month = isset($data->month) ? $data->month : date('n');
    $year  = isset($data->year) ? $data->year : date('Y');

    // 1. Delete current IT schedule for the specified month and year
    $deleteSql = "DELETE FROM duties_it WHERE MONTH(date) = :month AND YEAR(date) = :year";
    $stmtDelete = $pdo2->prepare($deleteSql);
    $stmtDelete->execute([':month' => $month, ':year' => $year]);

    // 2. Fetch OPD Card schedule for the specified month and year
    $fetchOpdSql = "SELECT oc.date, oc.rate_override, oc.is_special, eop.name, eop.position, eop.rate_holiday, eop.rate_weekday, eop.rate_parttime, eop.rate_holiday_special, eop.rate_weekday_special
                    FROM duties_opdcard oc
                    JOIN employees_opdcard eop ON oc.employees_opdcard_id = eop.id 
                    WHERE MONTH(oc.date) = :month AND YEAR(oc.date) = :year";
    $stmtOpd = $pdo2->prepare($fetchOpdSql);
    $stmtOpd->execute([':month' => $month, ':year' => $year]);
    $opdDuties = $stmtOpd->fetchAll(PDO::FETCH_ASSOC);

    $copiedCount = 0;
    
    // Cache for matched or created IT employees
    $itEmployeeCache = [];

    // Pre-fetch all IT employees to populate the cache
    $fetchItEmp = $pdo2->query("SELECT id, name FROM employees_it");
    while($row = $fetchItEmp->fetch(PDO::FETCH_ASSOC)){
        $itEmployeeCache[$row['name']] = $row['id'];
    }

    $insertItSql = "INSERT INTO duties_it (employees_id, date, rate_override, is_special) VALUES (:eid, :date, :rate, :is_special)";
    $stmtInsertIt = $pdo2->prepare($insertItSql);

    foreach ($opdDuties as $duty) {
        $name = $duty['name'];
        $itEmpId = null;

        if (isset($itEmployeeCache[$name])) {
            $itEmpId = $itEmployeeCache[$name];
        } else {
            // Create new IT employee based on OPD Card info
            $insertEmpSql = "INSERT INTO employees_it (name, position, rate_holiday, rate_weekday, rate_parttime, rate_holiday_special, rate_weekday_special) 
                             VALUES (:name, :position, :rate_holiday, :rate_weekday, :rate_parttime, :rate_holiday_special, :rate_weekday_special)";
            $stmtInsertEmp = $pdo2->prepare($insertEmpSql);
            $stmtInsertEmp->execute([
                ':name' => $name,
                ':position' => $duty['position'] ?? '',
                ':rate_holiday' => $duty['rate_holiday'] ?? 0,
                ':rate_weekday' => $duty['rate_weekday'] ?? 0,
                ':rate_parttime' => $duty['rate_parttime'] ?? 0,
                ':rate_holiday_special' => $duty['rate_holiday_special'] ?? 0,
                ':rate_weekday_special' => $duty['rate_weekday_special'] ?? 0
            ]);
            $itEmpId = $pdo2->lastInsertId();
            $itEmployeeCache[$name] = $itEmpId; // Add to cache
        }

        // Insert into duties_it
        $stmtInsertIt->execute([
            ':eid' => $itEmpId,
            ':date' => $duty['date'],
            ':rate' => $duty['rate_override'],
            ':is_special' => $duty['is_special'] ?? 0
        ]);
        
        $copiedCount++;
    }

    echo json_encode([
        'status' => 'success',
        'message' => "คัดลอกตารางเวรสำเร็จทั้งหมด $copiedCount รายการ"
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
