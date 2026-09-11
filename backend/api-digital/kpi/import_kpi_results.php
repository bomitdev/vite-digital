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

    // N+1 Query Optimization: Pre-fetch all referenced KPI Codes
    $uniqueCodes = [];
    foreach ($data as $row) {
        $kpiCode = $row['KPI_Code'] ?? '';
        if ($kpiCode && !in_array($kpiCode, $uniqueCodes)) {
            $uniqueCodes[] = $kpiCode;
        }
    }

    $kpiMap = [];
    if (!empty($uniqueCodes)) {
        $placeholders = implode(',', array_fill(0, count($uniqueCodes), '?'));
        $stmt = $pdo2->prepare("SELECT id, code, kpi_periodicity, target_value FROM kpi_definitions WHERE code IN ($placeholders)");
        $stmt->execute($uniqueCodes);
        $kpiList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($kpiList as $kpi) {
            $kpiMap[$kpi['code']] = $kpi;
        }
    }

    foreach ($data as $index => $row) {
        $rowNum = $index + 2; // Excel row number (assuming header is row 1)
        $kpiCode = $row['KPI_Code'] ?? '';
        $fiscalYear = isset($row['Fiscal_Year']) ? intval($row['Fiscal_Year']) : 0;
        $periodNum = isset($row['Period_Number']) ? intval($row['Period_Number']) : 0;
        
        // Validation: Actual_Value must be numeric
        $actualValueRaw = $row['Actual_Value'] ?? null;
        if ($actualValueRaw === null || $actualValueRaw === '') {
            $errors[] = "แถว $rowNum: ข้อมูลไม่ครบถ้วน (ต้องระบุ Actual_Value)";
            continue;
        }
        if (!is_numeric($actualValueRaw)) {
            $errors[] = "แถว $rowNum: ค่าผลงาน (Actual_Value) ต้องเป็นตัวเลขเท่านั้น (พบค่า: '$actualValueRaw')";
            continue;
        }
        $actualValue = floatval($actualValueRaw);

        if (!$kpiCode || !$fiscalYear || !$periodNum) {
            $errors[] = "แถว $rowNum: ข้อมูลไม่ครบถ้วน (ต้องระบุ KPI_Code, Fiscal_Year, Period_Number)";
            continue;
        }

        if (!isset($kpiMap[$kpiCode])) {
            $errors[] = "แถว $rowNum: ไม่พบรหัสตัวชี้วัด '$kpiCode' ในระบบ";
            continue;
        }

        $kpi = $kpiMap[$kpiCode];
        $kpiId = $kpi['id'];
        $periodicity = $kpi['kpi_periodicity'] ?? 'month';
        
        $targetSnapshotRaw = $row['Target_Snapshot'] ?? null;
        $targetSnapshot = null;
        if ($targetSnapshotRaw !== null && $targetSnapshotRaw !== '') {
            if (!is_numeric($targetSnapshotRaw)) {
                $errors[] = "แถว $rowNum: ค่าเป้าหมาย (Target_Snapshot) ต้องเป็นตัวเลขเท่านั้น";
                continue;
            }
            $targetSnapshot = floatval($targetSnapshotRaw);
        } else {
            $targetSnapshot = $kpi['target_value'];
        }

        $note = $row['Note'] ?? null;

        // Calculate period_date
        $year = $fiscalYear - 543;
        $periodDate = "";

        if ($periodicity === 'quarter') {
            if ($periodNum === 1) $periodDate = ($year - 1) . "-10-01";
            elseif ($periodNum === 2) $periodDate = "$year-01-01";
            elseif ($periodNum === 3) $periodDate = "$year-04-01";
            elseif ($periodNum === 4) $periodDate = "$year-07-01";
            else {
                $errors[] = "แถว $rowNum: เลขไตรมาส (Period_Number) ไม่ถูกต้อง (ต้องเป็น 1-4)";
                continue;
            }
        } elseif ($periodicity === 'Semiannual report') {
            if ($periodNum === 1) $periodDate = ($year - 1) . "-10-01";
            elseif ($periodNum === 2) $periodDate = "$year-04-01";
            else {
                $errors[] = "แถว $rowNum: เล็ขรอบครึ่งปี ไม่ถูกต้อง (ต้องเป็น 1-2)";
                continue;
            }
        } elseif ($periodicity === 'year') {
            $periodDate = ($year - 1) . "-10-01";
        } else {
            // Month
            if ($periodNum < 1 || $periodNum > 12) {
                $errors[] = "แถว $rowNum: เลขเดือน (Period_Number) ไม่ถูกต้อง (ต้องเป็น 1-12)";
                continue;
            }
            $calcYear = $year;
            if ($periodNum >= 10) {
                $calcYear = $year - 1;
            }
            $month = str_pad($periodNum, 2, '0', STR_PAD_LEFT);
            $periodDate = "$calcYear-$month-01";
        }

        try {
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
        } catch (PDOException $ex) {
            // Catch row-level database errors and continue
            $errors[] = "แถว $rowNum: เกิดข้อผิดพลาดในการบันทึกข้อมูลเข้าฐานข้อมูล (" . $ex->getMessage() . ")";
        }
    }

    // Always commit what we successfully processed
    $pdo2->commit();

    echo json_encode([
        'status' => 'success', 
        'message' => "บันทึกสำเร็จ $importedCount รายการ" . (count($errors) > 0 ? " (พบข้อผิดพลาด " . count($errors) . " รายการ)" : ""),
        'errors' => $errors
    ]);

} catch (Exception $e) {
    if (isset($pdo2) && $pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
