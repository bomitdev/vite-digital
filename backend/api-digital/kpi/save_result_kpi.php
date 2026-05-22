<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . '/../../config.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    // year_thai, period_number
    $year = intval($data['year_thai']) - 543;
    $periodNum = intval($data['period_number']);

    // Determine periodicity from DB to be safe or infer from input?
    // Let's first fetch the KPI definition to know its type
    $stmt = $pdo2->prepare("SELECT kpi_periodicity, target_value FROM kpi_definitions WHERE id = ?");
    $stmt->execute([$data['kpi_id']]);
    $kpi = $stmt->fetch();

    if (!$kpi) {
        throw new Exception("KPI not found");
    }

    $periodicity = $kpi['kpi_periodicity'] ?? 'month';
    $targetSnapshot = $kpi['target_value'];

    $periodDate = "";
    if ($periodicity === 'quarter') {
        // Q1: Oct-Dec (Year-1), Q2: Jan-Mar, Q3: Apr-Jun, Q4: Jul-Sep
        if ($periodNum === 1) {
            $prevYear = $year - 1;
            $periodDate = "$prevYear-10-01";
        } elseif ($periodNum === 2) {
            $periodDate = "$year-01-01";
        } elseif ($periodNum === 3) {
            $periodDate = "$year-04-01";
        } else {
            $periodDate = "$year-07-01";
        }
    } elseif ($periodicity === 'year') {
        // Fiscal Year starts Oct 1 of previous year
        $prevYear = $year - 1;
        $periodDate = "$prevYear-10-01";
    } elseif ($periodicity === 'Semiannual report') {
        if ($periodNum === 1) {
            $prevYear = $year - 1;
            $periodDate = "$prevYear-10-01";
        } else {
            $periodDate = "$year-04-01";
        }
    } else {
        // Month
        // Fiscal Year Logic: Oct (10), Nov (11), Dec (12) belong to previous calendar year
        $calcYear = $year;
        if ($periodNum >= 10) {
            $calcYear = $year - 1;
        }
        $month = str_pad($periodNum, 2, '0', STR_PAD_LEFT);
        $periodDate = "$calcYear-$month-01";
    }

    // We expect actual_value from frontend directly or numerator/denominator
    // Logic: if num/denom provided, calculate actual.
    // However, the new system mostly focuses on the final value for the dashboard.
    // Let's support both if possible or stick to actual_value.

    // Calculate actual value if components provided
    $actualValue = null;
    if (isset($data['numerator']) && isset($data['denominator']) && $data['denominator'] != 0) {
        // Assume % or rate. 
        // Need to check unit to be precise, but for now let's save the calculated value.
        // Or if data['actual_value'] is sent directly.
        if (isset($data['actual_value'])) {
            $actualValue = $data['actual_value'];
        } else {
            // Simple calculation (e.g. %)
            $actualValue = ($data['numerator'] / $data['denominator']) * 100; // Default to %? 
            // Better to let frontend calc and send actual_value to avoid unit ambiguity.
        }
    } elseif (isset($data['actual_value'])) {
        $actualValue = $data['actual_value'];
    } else {
        throw new Exception("Missing value data");
    }

    // Get current target snapshot
    $stmt = $pdo2->prepare("SELECT target_value FROM kpi_definitions WHERE id = ?");
    $stmt->execute([$data['kpi_id']]);
    $kpi = $stmt->fetch();
    $targetSnapshot = $kpi ? $kpi['target_value'] : 0;

    $sql = "INSERT INTO kpi_entries (kpi_id, period_date, actual_value, target_value_snapshot) 
            VALUES (:kpi_id, :period_date, :actual, :target)
            ON DUPLICATE KEY UPDATE 
                actual_value = VALUES(actual_value),
                target_value_snapshot = VALUES(target_value_snapshot)";

    $stmt = $pdo2->prepare($sql);
    $stmt->execute([
        ':kpi_id' => $data['kpi_id'],
        ':period_date' => $periodDate,
        ':actual' => $actualValue,
        ':target' => $targetSnapshot
    ]);

    // Return status for frontend message
    // Calculate Pass/Fail for immediate feedback
    // (Simplification: assuming >= for now or fetching operator)

    echo json_encode([
        'status' => 'success',
        'actual_value' => $actualValue
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
