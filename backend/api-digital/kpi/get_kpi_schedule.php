<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

try {
    $year = isset($_GET['year']) ? intval($_GET['year']) : (date("Y") + 543);

    // Auto-adjust if currently in Oct-Dec (Early next FY)
    if (!isset($_GET['year']) && date("m") >= 10) {
        $year++;
    }

    $sql = "SELECT period_number, period_name, input_start_date, input_end_date 
            FROM kpi_schedule 
            WHERE fiscal_year = :year AND period_type = 'month'
            ORDER BY period_number";

    $stmt = $pdo2->prepare($sql);
    $stmt->execute([':year' => $year]);
    $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $schedule]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
