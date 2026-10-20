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

    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
    $endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    if ($limit <= 0 || $limit > 500) $limit = 100;

    $whereClauses = [];
    $params = [];

    if (!empty($search)) {
        $whereClauses[] = "(hn LIKE :search OR patient_name LIKE :search OR note LIKE :search OR created_by LIKE :search)";
        $params[':search'] = "%{$search}%";
    }

    if (!empty($startDate)) {
        $whereClauses[] = "DATE(created_at) >= :start_date";
        $params[':start_date'] = $startDate;
    }

    if (!empty($endDate)) {
        $whereClauses[] = "DATE(created_at) <= :end_date";
        $params[':end_date'] = $endDate;
    }

    $whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

    $sql = "
        SELECT 
            id, hn, patient_name, inr_value, prev_weekly_dose, percent_change,
            new_weekly_dose, daily_avg_dose, formula_label, has_major_bleeding,
            is_first_visit, primary_tablet_strength, daily_schedule_json,
            note, created_by, created_at
        FROM warfarin_calc_logs
        {$whereSql}
        ORDER BY created_at DESC, id DESC
        LIMIT {$limit}
    ";

    $stmt = $pdo2->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decode json schedule if needed
    foreach ($logs as &$log) {
        if (!empty($log['daily_schedule_json'])) {
            $log['daily_schedule'] = json_decode($log['daily_schedule_json'], true);
        } else {
            $log['daily_schedule'] = [];
        }
    }

    echo json_encode([
        'status' => 'success',
        'count' => count($logs),
        'data' => $logs
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
