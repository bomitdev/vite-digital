<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

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
            e.rate_parttime,
            e.rate_holiday_special,
            e.rate_weekday_special,
            d.date as duty_date,
            d.rate_override,
            d.is_special
        FROM employees_claim e 
        LEFT JOIN duties_claim d ON e.id = d.employees_claim_id 
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
        
        $r_hol = (int)$row['rate_holiday'];
        $r_wd = (int)$row['rate_weekday'];
        $r_pt = (int)$row['rate_holiday_special'];
        $r_pt_wd = (int)$row['rate_weekday_special'];

        if (!isset($employees[$empId])) {
            $employees[$empId] = [
                'id' => $empId,
                'name' => $row['name'],
                'position' => $row['position'],
                'rate_holiday' => $r_hol,
                'rate_weekday' => $r_wd,
                'duties' => array_fill(1, $daysInMonth, ''),
                'shift_type' => array_fill(1, $daysInMonth, ''), // X for weekday, O for holiday
                'total_days' => 0,
                'total_amount' => 0,
                'rate_breakdowns' => [],
                'duties_rate' => array_fill(1, $daysInMonth, null)
            ];

        }

        if ($row['duty_date']) {
            $day = (int)date('j', strtotime($row['duty_date']));
            $dutyDateString = $row['duty_date'];

            // Determine if Working Day (X) or Holiday (O)
            $isWorkingDay = in_array($dutyDateString, $govDates);
            
            // Applied Rate
            if (!empty($row['is_special'])) {
                if ($row['is_special'] == 2) {
                    $appliedRate = $employees[$empId]['rate_weekday_special'];
                } else {
                    $appliedRate = $employees[$empId]['rate_holiday_special'] > 0 
                        ? $employees[$empId]['rate_holiday_special'] 
                        : $employees[$empId]['rate_weekday_special'];
                }
            } else if (!empty($row['rate_override'])) {
                $appliedRate = (int)$row['rate_override'];
            } else {
                $appliedRate = $isWorkingDay ? $r_wd : $r_hol;
            }

            // Assign duty data
            $employees[$empId]['duties'][$day] = 'CLAIM';
            $employees[$empId]['shift_type'][$day] = $isWorkingDay ? 'X' : 'O';
            $employees[$empId]['duties_rate'][$day] = $appliedRate;
            
            if (!empty($row['is_special']) && $row['is_special'] == 2) {
                $employees[$empId]['duties_rate_double'][$day] = $appliedRate;
            } else {
                $employees[$empId]['duties_rate_normal'][$day] = $appliedRate;
            }

            $employees[$empId]['total_days']++;
            $employees[$empId]['total_amount'] += $appliedRate;

            // Group by rate and special status
            $breakdownKey = $appliedRate . '_' . ($row['is_special'] == 2 ? '2' : 'normal');
            if (!isset($employees[$empId]['rate_breakdowns'][$breakdownKey])) {
                $employees[$empId]['rate_breakdowns'][$breakdownKey] = [
                    'rate' => $appliedRate,
                    'total_days' => 0,
                    'total_amount' => 0,
                    'is_special' => ($row['is_special'] == 2)
                ];
            }
            $employees[$empId]['rate_breakdowns'][$breakdownKey]['total_days']++;
            $employees[$empId]['rate_breakdowns'][$breakdownKey]['total_amount'] += $appliedRate;
        }
    }

    // Prepare rate_breakdowns to be indexed arrays and sort descending
    foreach ($employees as &$emp) {
        if (empty($emp['rate_breakdowns'])) {
            $emp['rate_breakdowns'][] = [
                'rate' => $emp['rate_holiday'] ?: 0,
                'total_days' => 0,
                'total_amount' => 0
            ];
        } else {
            $emp['rate_breakdowns'] = array_values($emp['rate_breakdowns']);
            usort($emp['rate_breakdowns'], function($a, $b) {
                return $b['rate'] <=> $a['rate'];
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
