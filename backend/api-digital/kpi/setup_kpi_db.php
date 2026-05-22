<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

try {
    // 1. Create KPI Categories Table
    $sql = "CREATE TABLE IF NOT EXISTS kpi_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo2->exec($sql);

    // 2. Insert Default Categories
    $sql = "INSERT INTO kpi_categories (id, name, description) VALUES
    (1, 'ด้านการดูแลผู้ป่วย', 'ด้านการดูแลผู้ป่วย'),
    (2, 'ด้านการมุ่งเน้น ผู้ป่วยและผู้รับ', 'ด้านการมุ่งเน้น ผู้ป่วยและผู้รับ'),
    (3, 'ผลงาน', 'ผลงาน'),
    (4, 'ด้านอัตรากำลังคน', 'ด้านอัตรากำลังคน'),
    (5, 'ด้านการนำ', 'ด้านการนำ'),
    (6, 'ด้านประสิทธิผล ของกระบวน การทำงานที่สำคัญ', 'ด้านประสิทธิผล ของกระบวน การทำงานที่สำคัญ'),
    (7, 'ด้านการเงิน', 'ด้านการเงิน')
    ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description);";
    $pdo2->exec($sql);

    // 3. Create KPI Definitions Table
    $sql = "CREATE TABLE IF NOT EXISTS kpi_definitions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        target_value DECIMAL(10,2),
        target_operator VARCHAR(10) DEFAULT '>=',
        unit VARCHAR(50),
        frequency VARCHAR(50) DEFAULT 'Monthly',
        FOREIGN KEY (category_id) REFERENCES kpi_categories(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo2->exec($sql);

    // 4. Create KPI Entries Table
    $sql = "CREATE TABLE IF NOT EXISTS kpi_entries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        kpi_id INT NOT NULL,
        period_date DATE NOT NULL,
        actual_value DECIMAL(10,2),
        target_value_snapshot DECIMAL(10,2),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (kpi_id) REFERENCES kpi_definitions(id),
        UNIQUE KEY unique_entry (kpi_id, period_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo2->exec($sql);

    // 5. Seed Initial KPIs (Check if empty first to avoid duplicates if re-run without unique constraint on name)
    // Actually, let's just check if table is empty or use INSERT IGNORE based on known IDs if possible, 
    // but IDs might auto-increment. Let's check by name.

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
        $stmt = $pdo2->prepare("SELECT id FROM kpi_definitions WHERE name = ?");
        $stmt->execute([$kpi[1]]);
        if (!$stmt->fetch()) {
            $stmt = $pdo2->prepare("INSERT INTO kpi_definitions (category_id, name, description, target_value, target_operator, unit) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$kpi[0], $kpi[1], $kpi[2], $kpi[3], $kpi[4], $kpi[5]]);
        }
    }

    // 6. Seed some random dummy data for the last 3 months
    $stmt = $pdo2->query("SELECT id, target_value FROM kpi_definitions");
    $allKpis = $stmt->fetchAll();

    $months = [
        date('Y-m-01', strtotime('-2 months')),
        date('Y-m-01', strtotime('-1 month')),
        date('Y-m-01'),
    ];

    foreach ($months as $date) {
        foreach ($allKpis as $kpi) {
            // Generate semi-random value near target
            $target = $kpi['target_value'];
            $variance = $target * 0.2; // 20% variance
            // Random float
            $randomVal = $target + (mt_rand(-100, 100) / 100 * $variance);
            if ($randomVal < 0) $randomVal = 0;

            // Insert or Update
            $stmt = $pdo2->prepare("INSERT INTO kpi_entries (kpi_id, period_date, actual_value, target_value_snapshot) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE actual_value = VALUES(actual_value)");
            $stmt->execute([$kpi['id'], $date, round($randomVal, 2), $target]);
        }
    }

    echo json_encode(['message' => 'KPI Tables created and seeded successfully']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
