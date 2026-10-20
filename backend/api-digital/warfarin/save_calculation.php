<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config.php';

try {
    if (!isset($pdo2)) {
        throw new Exception("Database connection missing (pdo2).");
    }

    // Auto-create table if not exists
    $createTableSql = "
    CREATE TABLE IF NOT EXISTS `warfarin_calc_logs` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `hn` VARCHAR(20) NULL COMMENT 'HN ผู้ป่วย',
      `patient_name` VARCHAR(150) NULL COMMENT 'ชื่อผู้ป่วย',
      `inr_value` DECIMAL(5,2) NOT NULL COMMENT 'ค่า INR (A)',
      `prev_weekly_dose` DECIMAL(6,2) NOT NULL COMMENT 'ขนาดยาเดิม มก./สัปดาห์ (B)',
      `percent_change` DECIMAL(5,2) NOT NULL COMMENT 'เปอร์เซ็นต์ปรับยา % (C)',
      `new_weekly_dose` DECIMAL(6,2) NOT NULL COMMENT 'ขนาดยาใหม่คำนวณได้ มก./สัปดาห์',
      `daily_avg_dose` DECIMAL(6,2) NOT NULL COMMENT 'เฉลี่ยต่อวัน มก./วัน',
      `formula_label` VARCHAR(100) NULL,
      `has_major_bleeding` TINYINT(1) DEFAULT 0,
      `is_first_visit` TINYINT(1) DEFAULT 0,
      `primary_tablet_strength` DECIMAL(4,2) DEFAULT 3.0,
      `daily_schedule_json` TEXT NULL,
      `note` VARCHAR(255) NULL,
      `created_by` VARCHAR(100) NULL,
      `client_ip` VARCHAR(45) NULL,
      `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
      INDEX `idx_hn` (`hn`),
      INDEX `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo2->exec($createTableSql);

    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (!$data) {
        throw new Exception("Invalid JSON payload.");
    }

    $hn = isset($data['hn']) ? trim($data['hn']) : null;
    $patientName = isset($data['patient_name']) ? trim($data['patient_name']) : null;
    $inrValue = isset($data['inr_value']) ? (float)$data['inr_value'] : 0.0;
    $prevWeeklyDose = isset($data['prev_weekly_dose']) ? (float)$data['prev_weekly_dose'] : 0.0;
    $percentChange = isset($data['percent_change']) ? (float)$data['percent_change'] : 0.0;
    $newWeeklyDose = isset($data['new_weekly_dose']) ? (float)$data['new_weekly_dose'] : 0.0;
    $dailyAvgDose = isset($data['daily_avg_dose']) ? (float)$data['daily_avg_dose'] : 0.0;
    $formulaLabel = isset($data['formula_label']) ? trim($data['formula_label']) : '';
    $hasMajorBleeding = !empty($data['has_major_bleeding']) ? 1 : 0;
    $isFirstVisit = !empty($data['is_first_visit']) ? 1 : 0;
    $primaryTabletStrength = isset($data['primary_tablet_strength']) ? (float)$data['primary_tablet_strength'] : 3.0;
    $dailyScheduleJson = isset($data['daily_schedule_json']) ? (is_string($data['daily_schedule_json']) ? $data['daily_schedule_json'] : json_encode($data['daily_schedule_json'], JSON_UNESCAPED_UNICODE)) : null;
    $note = isset($data['note']) ? trim($data['note']) : null;
    $createdBy = isset($data['created_by']) ? trim($data['created_by']) : 'Pharmacist';
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';

    $stmt = $pdo2->prepare("
        INSERT INTO warfarin_calc_logs (
            hn, patient_name, inr_value, prev_weekly_dose, percent_change,
            new_weekly_dose, daily_avg_dose, formula_label, has_major_bleeding,
            is_first_visit, primary_tablet_strength, daily_schedule_json,
            note, created_by, client_ip, created_at
        ) VALUES (
            :hn, :patient_name, :inr_value, :prev_weekly_dose, :percent_change,
            :new_weekly_dose, :daily_avg_dose, :formula_label, :has_major_bleeding,
            :is_first_visit, :primary_tablet_strength, :daily_schedule_json,
            :note, :created_by, :client_ip, NOW()
        )
    ");

    $stmt->execute([
        ':hn' => $hn,
        ':patient_name' => $patientName,
        ':inr_value' => $inrValue,
        ':prev_weekly_dose' => $prevWeeklyDose,
        ':percent_change' => $percentChange,
        ':new_weekly_dose' => $newWeeklyDose,
        ':daily_avg_dose' => $dailyAvgDose,
        ':formula_label' => $formulaLabel,
        ':has_major_bleeding' => $hasMajorBleeding,
        ':is_first_visit' => $isFirstVisit,
        ':primary_tablet_strength' => $primaryTabletStrength,
        ':daily_schedule_json' => $dailyScheduleJson,
        ':note' => $note,
        ':created_by' => $createdBy,
        ':client_ip' => $clientIp
    ]);

    $insertedId = $pdo2->lastInsertId();

    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกประวัติการคำนวณขนาดยา Warfarin เรียบร้อยแล้ว',
        'data' => [
            'id' => $insertedId,
            'hn' => $hn,
            'new_weekly_dose' => $newWeeklyDose
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
