<?php
require __DIR__ . '/../../config.php';

// Generate schedule for a given Fiscal Year (default 2568)
$fiscal_year = isset($_GET['year']) ? intval($_GET['year']) : 2568;

// Helper to calculate dates
function getMonthDates($fy, $monthIndex)
{
    // $monthIndex: 1=Oct, 2=Nov, ..., 12=Sep
    // FY 2568: Oct 2024 - Sep 2025
    $gYearStart = $fy - 543 - 1; // 2024

    // Calculate actual year and month
    if ($monthIndex <= 3) { // Oct, Nov, Dec
        $year = $gYearStart;
        $month = $monthIndex + 9; // 1->10, 2->11, 3->12
    } else { // Jan - Sep
        $year = $gYearStart + 1;
        $month = $monthIndex - 3; // 4->1, 12->9
    }

    $startDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
    $endDate = date("Y-m-t", strtotime($startDate));

    return [$startDate, $endDate];
}

$months = [
    1 => 'ตุลาคม',
    2 => 'พฤศจิกายน',
    3 => 'ธันวาคม',
    4 => 'มกราคม',
    5 => 'กุมภาพันธ์',
    6 => 'มีนาคม',
    7 => 'เมษายน',
    8 => 'พฤษภาคม',
    9 => 'มิถุนายน',
    10 => 'กรกฎาคม',
    11 => 'สิงหาคม',
    12 => 'กันยายน'
];

try {
    $pdo2->beginTransaction();

    // 1. Monthly Schedule
    foreach ($months as $num => $name) {
        list($start, $end) = getMonthDates($fiscal_year, $num);

        // Allowed Input: Full month (Start to End)
        // Unlock: End of month to End of Fiscal Year (Sep 30)
        // Report Date: 5th of next month (approx) or 5th of current? 
        // Image says: Range Input Begin = 1st of month. Range Input End = End of month.
        // Let's stick to image logic: Input Input Begin/End = Month Start/End.

        $sql = "INSERT INTO kpi_schedule 
                (fiscal_year, period_type, period_number, period_name, input_start_date, input_end_date)
                VALUES (:fy, 'month', :num, :name, :start, :end)
                ON DUPLICATE KEY UPDATE 
                input_start_date = VALUES(input_start_date),
                input_end_date = VALUES(input_end_date)";

        $stmt = $pdo2->prepare($sql);
        $stmt->execute([
            ':fy' => $fiscal_year,
            ':num' => $num,
            ':name' => $name,
            ':start' => $start,
            ':end' => $end
        ]);
    }

    // 2. Quarterly Schedule (Optional, can add dates if needed)
    // Q1: Oct-Dec, Q2: Jan-Mar, Q3: Apr-Jun, Q4: Jul-Sep
    // For now, let's just seed months as they are the primary input unit.

    $pdo2->commit();
    echo "Schedule generated for Fiscal Year $fiscal_year";
} catch (Exception $e) {
    $pdo2->rollBack();
    echo "Error: " . $e->getMessage();
}
