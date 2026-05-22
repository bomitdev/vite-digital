<?php
require __DIR__ . '/../../config.php';
require __DIR__ . '/../../auth_utils.php';

// Protect this endpoint
$userData = authGuard();

$year = isset($_GET['year']) ? filter_var($_GET['year'], FILTER_VALIDATE_INT) : (int)date('Y');
$month = isset($_GET['month']) ? filter_var($_GET['month'], FILTER_VALIDATE_INT) : (int)date('n');

if ($year === false || $month === false || $month < 1 || $month > 12) {
    echo json_encode(["status" => "error", "error" => "Invalid year or month"]);
    exit;
}

try {
    // 1. Get government working dates (holidays/weekends are mapped where this query has gaps)
    $stmtGov = $pdo3->prepare("SELECT gov_date FROM 10985_hos_government_date WHERE YEAR(gov_date) = :year AND MONTH(gov_date) = :month");
    $stmtGov->execute([':year' => $year, ':month' => $month]);
    $govDatesRows = $stmtGov->fetchAll(PDO::FETCH_ASSOC);
    $govDates = array_map(function ($row) {
        return $row['gov_date'];
    }, $govDatesRows);

    // 2. Get Employees and their duties
    $sql = "
    SELECT
    e.id,
    e.name,
    e.position,
    e.rate_holiday,
    e.rate_weekday,
    e.rate_holiday_special,
    e.rate_weekday_special,
    e.rate_parttime,
    d.date as duty_date,
    d.rate_override,
    d.is_special
    FROM employees_it e
    LEFT JOIN duties_it d ON e.id = d.employees_id
    AND YEAR(d.date) = :year
    AND MONTH(d.date) = :month
    ORDER BY e.id, d.date ASC
    ";

    $stmt = $pdo2->prepare($sql);
    $stmt->execute([':year' => $year, ':month' => $month]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get number of days in the requested month
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

    // Process data to build the report structure
    $employees = [];
    foreach ($rows as $row) {
        $empId = $row['id'];
        if (!isset($employees[$empId])) {
            $employees[$empId] = [
                'id' => $empId,
                'name' => $row['name'],
                'position' => $row['position'],
                'rate_holiday' => (int)$row['rate_holiday'],
                'rate_weekday' => (int)$row['rate_weekday'],
                'rate_holiday_special' => (int)$row['rate_holiday_special'],
                'rate_weekday_special' => (int)$row['rate_weekday_special'],
                'duties' => array_fill(1, $daysInMonth, ''),
                'shift_type' => array_fill(1, $daysInMonth, ''), // X for weekday, O for holiday
                'total_days' => 0,
                'total_holiday_days' => 0,
                'total_weekday_days' => 0,
                'total_amount' => 0,
                'rate_breakdowns' => [],
                'duties_rate' => array_fill(1, $daysInMonth, null),
                'duties_special' => array_fill(1, $daysInMonth, false)
            ];
        }

        if ($row['duty_date']) {
            $day = (int)date('j', strtotime($row['duty_date']));
            $dutyDateString = $row['duty_date'];

            // Determine if Working Day (X) or Holiday (O)
            $isWorkingDay = in_array($dutyDateString, $govDates);
            $shiftType = $isWorkingDay ? 'X' : 'O';

            // แสดงวันทำการและวันหยุดทั้งหมด แต่คำนวณเงินให้เฉพาะวันหยุด
            $employees[$empId]['duties'][$day] = 'IT';
            $employees[$empId]['shift_type'][$day] = $shiftType;
            $employees[$empId]['duties_special'][$day] = ($row['is_special'] == 2);
            
            // Track if the day has double or normal rates (handles multiple duties per day)
            $employees[$empId]['duties_is_double'][$day] = ($employees[$empId]['duties_is_double'][$day] ?? false) || ($row['is_special'] == 2);
            $employees[$empId]['duties_is_normal'][$day] = ($employees[$empId]['duties_is_normal'][$day] ?? false) || ($row['is_special'] != 2);
            $employees[$empId]['total_days']++;

            if (!$isWorkingDay) {
                // Determine rate to use
                if (!empty($row['is_special'])) {
                    if ($row['is_special'] == 2) {
                        // is_special = 2 หมายถึง "เรทวันหยุด 2 เท่า" ซึ่งเก็บอยู่ในช่อง rate_weekday_special
                        $appliedRate = $employees[$empId]['rate_weekday_special'];
                    } else {
                        // is_special = 1 หมายถึง "เรทวันหยุดพิเศษหรือปกติ" ซึ่งเก็บอยู่ในช่อง rate_holiday_special
                        // ถ้าไม่มีให้ fallback ไปดึงค่าจาก rate_weekday_special
                        $appliedRate = $employees[$empId]['rate_holiday_special'] > 0 
                            ? $employees[$empId]['rate_holiday_special'] 
                            : $employees[$empId]['rate_weekday_special'];
                    }
                } else if (!empty($row['rate_override'])) {
                    $appliedRate = (int)$row['rate_override'];
                } else {
                    $appliedRate = $employees[$empId]['rate_holiday'];
                }
                $employees[$empId]['duties_rate'][$day] = $appliedRate;
                
                if ($row['is_special'] == 2) {
                    $employees[$empId]['duties_rate_double'][$day] = $appliedRate;
                } else {
                    $employees[$empId]['duties_rate_normal'][$day] = $appliedRate;
                }

                // วันหยุดราชการ (คำนวณเงิน)
                $employees[$empId]['total_holiday_days']++;
                $employees[$empId]['total_amount'] += $appliedRate;

                // Group by rate and is_special to prevent combining identical rates
                $isSpecial = ($row['is_special'] == 2);
                $breakdownKey = $appliedRate . ($isSpecial ? '_special' : '_normal');
                
                if (!isset($employees[$empId]['rate_breakdowns'][$breakdownKey])) {
                    $employees[$empId]['rate_breakdowns'][$breakdownKey] = [
                        'rate' => $appliedRate,
                        'total_holiday_days' => 0,
                        'total_amount' => 0,
                        'is_special' => $isSpecial
                    ];
                }
                $employees[$empId]['rate_breakdowns'][$breakdownKey]['total_holiday_days']++;
                $employees[$empId]['rate_breakdowns'][$breakdownKey]['total_amount'] += $appliedRate;
            } else {
                // วันทำการ (เอามาโชว์เฉยๆ ไม่คำนวณเงิน)
                $employees[$empId]['total_weekday_days']++;
            }
        }
    }

    // Prepare rate_breakdowns to be indexed arrays
    foreach ($employees as &$emp) {
        if (empty($emp['rate_breakdowns'])) {
            $emp['rate_breakdowns'] = [
                ['rate' => $emp['rate_holiday'], 'total_holiday_days' => 0, 'total_amount' => 0, 'is_special' => false]
            ];
        } else {
            $emp['rate_breakdowns'] = array_values($emp['rate_breakdowns']);
            usort($emp['rate_breakdowns'], function($a, $b) {
                // Sort by is_special first, then rate
                if ($a['is_special'] !== $b['is_special']) {
                    return $a['is_special'] ? 1 : -1;
                }
                return $a['rate'] <=> $b['rate'];
            });
        }
    }
    unset($emp);

    // Prepare day categories for the month header
    $monthDaysInfo = [];
    for ($i = 1; $i <= $daysInMonth; $i++) {
        $dateStr = sprintf("%04d-%02d-%02d", $year, $month, $i);
        $isWorkingDay = in_array($dateStr, $govDates);
        $monthDaysInfo[$i] = $isWorkingDay ? 'X' : 'O';
    }

    echo json_encode([
        "status" => "success",
        "year" => $year,
        "month" => $month,
        "daysInMonth" => $daysInMonth,
        "monthDaysInfo" => $monthDaysInfo,
        "data" => array_values($employees)
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "error" => "Database error.", "details" => $e->getMessage()]);
}
