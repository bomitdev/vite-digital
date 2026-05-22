<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require __DIR__ . '/../../config.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['month']) || !isset($data['year'])) {
        throw new Exception("Month and Year are required.");
    }

    $month = $data['month'];
    $year = $data['year'];

    // 1. Get holidays for the month
    $stmtHoliday = $pdo3->prepare("SELECT holiday_date FROM holiday WHERE MONTH(holiday_date) = :m AND YEAR(holiday_date) = :y");
    $stmtHoliday->execute([':m' => $month, ':y' => $year]);
    $holidays = $stmtHoliday->fetchAll(PDO::FETCH_COLUMN); // Array of 'YYYY-MM-DD'

    // 2. Clear existing entries for this month (optional, to avoid duplicates if re-run, though user said 'add if not exist')
    // Logic: User said "Click button if empty". So mainly for inserting.
    // However, to be safe, we can check existence for each date or just insert ignore.
    // Let's rely on logic: Generating all dates for the month that are NOT holidays.

    $startDate = new DateTime("$year-$month-01");
    $endDate = new DateTime("$year-$month-01");
    $endDate->modify('last day of this month');

    $sqlInsert = "INSERT INTO 10985_hos_government_date (gov_date) VALUES (:date)";
    $stmtInsert = $pdo3->prepare($sqlInsert);

    $count = 0;

    // Check if dates already exist for this month to avoid duplicates/errors if table has unique constraint
    $stmtCheck = $pdo3->prepare("SELECT COUNT(*) FROM 10985_hos_government_date WHERE MONTH(gov_date) = :m AND YEAR(gov_date) = :y");
    $stmtCheck->execute([':m' => $month, ':y' => $year]);
    if ($stmtCheck->fetchColumn() > 0) {
        // Already has data, maybe don't regenerate or delete first?
        // User requirement: "If empty then add button". So API assumes cleaner slate or careful insertion.
        // Let's delete ensuring clean slate for "Generation" action.
        $stmtDelete = $pdo3->prepare("DELETE FROM 10985_hos_government_date WHERE MONTH(gov_date) = :m AND YEAR(gov_date) = :y");
        $stmtDelete->execute([':m' => $month, ':y' => $year]);
    }

    while ($startDate <= $endDate) {
        $currentDateStr = $startDate->format('Y-m-d');

        // Check if holiday
        if (!in_array($currentDateStr, $holidays)) {
            // Not a holiday -> Insert
            $stmtInsert->execute([':date' => $currentDateStr]);
            $count++;
        }

        $startDate->modify('+1 day');
    }

    echo json_encode(['status' => 'success', 'message' => "Generated $count working days.", 'count' => $count]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
