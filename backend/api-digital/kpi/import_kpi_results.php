<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!is_array($data)) {
        throw new Exception("Invalid data format");
    }

    $pdo2->beginTransaction();
    $importedCount = 0;
    $errors = [];

    foreach ($data as $index => $row) {
        $kpiCode = $row['KPI_Code'] ?? '';
        $fiscalYear = isset($row['Fiscal_Year']) ? intval($row['Fiscal_Year']) : 0;
        $periodNum = isset($row['Period_Number']) ? intval($row['Period_Number']) : 0;
        $actualValue = isset($row['Actual_Value']) && $row['Actual_Value'] !== '' ? floatval($row['Actual_Value']) : null;
        $targetSnapshot = isset($row['Target_Snapshot']) && $row['Target_Snapshot'] !== '' ? floatval($row['Target_Snapshot']) : null;
        $note = $row['Note'] ?? null;

        if (!$kpiCode || !$fiscalYear || !$periodNum || $actualValue === null) {
            $errors[] = "Row " . ($index + 2) . ": ข้อมูลไม่ครบถ้วน (ต้องมี KPI_Code, Fiscal_Year, Period_Number, Actual_Value)";
            continue;
        }

        $stmt = $pdo2->prepare("SELECT id, kpi_periodicity, target_value FROM kpi_definitions WHERE code = ? LIMIT 1");
        $stmt->execute([$kpiCode]);
        $kpi = $stmt->fetch();

        if (!$kpi) {
            $errors[] = "Row " . ($index + 2) . ": ไม่พบรหัสตัวชี้วัด '$kpiCode' ในระบบ";
            continue;
        }

        $kpiId = $kpi['id'];
        $periodicity = $kpi['kpi_periodicity'] ?? 'month';
        
        if ($targetSnapshot === null) {
            $targetSnapshot = $kpi['target_value'];
        }

        // Calculate period_date
        $year = $fiscalYear - 543;
        $periodDate = "";

        if ($periodicity === 'quarter') {
            if ($periodNum === 1) $periodDate = ($year - 1) . "-10-01";
            elseif ($periodNum === 2) $periodDate = "$year-01-01";
            elseif ($periodNum === 3) $periodDate = "$year-04-01";
            else $periodDate = "$year-07-01";
        } elseif ($periodicity === 'Semiannual report') {
            if ($periodNum === 1) $periodDate = ($year - 1) . "-10-01";
            else $periodDate = "$year-04-01";
        } elseif ($periodicity === 'year') {
            $periodDate = ($year - 1) . "-10-01";
        } else {
            // Month
            $calcYear = $year;
            if ($periodNum >= 10) {
                $calcYear = $year - 1;
            }
            $month = str_pad($periodNum, 2, '0', STR_PAD_LEFT);
            $periodDate = "$calcYear-$month-01";
        }

        // Ensure notes column exists in schema. It was defined in setup_kpi_db.php, but let's be safe.
        // If it throws an error, it's better to add notes or catch it, but schema has `notes`.
        $sql = "INSERT INTO kpi_entries (kpi_id, period_date, actual_value, target_value_snapshot, notes) 
                VALUES (:kpi_id, :period_date, :actual, :target, :notes)
                ON DUPLICATE KEY UPDATE 
                    actual_value = VALUES(actual_value),
                    target_value_snapshot = VALUES(target_value_snapshot),
                    notes = VALUES(notes)";
                    
        $insertStmt = $pdo2->prepare($sql);
        $insertStmt->execute([
            ':kpi_id' => $kpiId,
            ':period_date' => $periodDate,
            ':actual' => $actualValue,
            ':target' => $targetSnapshot,
            ':notes' => $note
        ]);

        if ($note) {
            $updateAnalysis = $pdo2->prepare("UPDATE kpi_definitions SET analysis = :note WHERE id = :kpi_id");
            $updateAnalysis->execute([
                ':note' => $note,
                ':kpi_id' => $kpiId
            ]);
        }

        $importedCount++;
    }

    $pdo2->commit();

    echo json_encode([
        'status' => 'success', 
        'message' => "นำเข้าข้อมูลสำเร็จ $importedCount รายการ",
        'errors' => $errors
    ]);

} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
