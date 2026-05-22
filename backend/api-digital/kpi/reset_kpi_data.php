<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

try {
    // Disable foreign key checks to allow truncation
    $pdo2->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Truncate tables
    $pdo2->exec("TRUNCATE TABLE kpi_entries");
    $pdo2->exec("TRUNCATE TABLE kpi_definitions");

    // Re-enable foreign key checks
    $pdo2->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Re-seed Logic (copied from setup_kpi_db.php for convenience)
    $kpis = [
        [1, 'Unplanned Readmission Rate', 'อัตราการกลับมารักษาซ้ำภายใน 28 วัน', 2.00, '<=', '%'],
        [1, 'NI Rate', 'อัตราการติดเชื้อในโรงพยาบาล', 2.50, '<=', 'per 1000'],
        [2, 'Medication Error Rate', 'ความคลาดเคลื่อนทางยา', 0.00, '=', 'events'],
        [2, 'Patient Fall Rate', 'อัตราการพลัดตกหกล้ม', 1.00, '<=', 'per 1000'],
        [3, 'Patient Satisfaction Score', 'คะแนนความพึงพอใจผู้ป่วย', 85.00, '>=', '%'],
        [3, 'OPD Waiting Time', 'ระยะเวลารอคอยเฉลี่ยแผนกผู้ป่วยนอก', 45.00, '<=', 'min'],
        [4, 'Bed Occupancy Rate', 'อัตราการครองเตียง', 80.00, '>=', '%'],
        [4, 'Average Length of Stay', 'วันนอนเฉลี่ย', 3.50, '<=', 'days'],
        [5, 'EBITDA Margin', 'กำไรก่อนดอกเบี้ย ภาษี และค่าเสื่อม', 15.00, '>=', '%'],
        [5, 'Revenue per OPD Visit', 'รายได้เฉลี่ยต่อผู้ป่วยนอก', 1500.00, '>=', 'THB']
    ];

    foreach ($kpis as $kpi) {
        $stmt = $pdo2->prepare("INSERT INTO kpi_definitions (category_id, name, description, target_value, target_operator, unit) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$kpi[0], $kpi[1], $kpi[2], $kpi[3], $kpi[4], $kpi[5]]);
    }

    echo json_encode(['status' => 'success', 'message' => 'KPI Data Reset Successfully']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
