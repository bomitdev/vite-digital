<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

// ใช้ $pdo3 สำหรับ hosoffice ที่มีข้อมูลพนักงาน
if (!isset($pdo3)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

try {
    // ดึงรายชื่อคนในกลุ่มงานสุขภาพดิจิทัล
    // อ้างอิงจากตาราง hr_person
    $sql = "
        SELECT 
            p.ID as id, 
            CONCAT(p.HR_FNAME, ' ', p.HR_LNAME) as fullname,
            d.HR_DEPARTMENT_SUB_NAME as department_name
        FROM hr_person p
        LEFT JOIN hr_department_sub d ON p.HR_DEPARTMENT_SUB_ID = d.HR_DEPARTMENT_SUB_ID
        WHERE d.HR_DEPARTMENT_SUB_NAME LIKE '%ดิจิทัล%' OR d.HR_DEPARTMENT_SUB_NAME LIKE '%IT%'
        ORDER BY p.HR_FNAME ASC
    ";

    $stmt = $pdo3->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $users
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'DB Error: ' . $e->getMessage()
    ]);
}
