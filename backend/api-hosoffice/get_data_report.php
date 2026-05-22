<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth_utils.php';

// Secure Auth
$userData = authGuard();
$user_id = $userData['uid'];


try {
    // 1. Check User Type
    $stmtUser = $pdo3->prepare("SELECT USER_TYPE FROM hr_person WHERE id = :id");
    $stmtUser->bindParam(":id", $user_id);
    $stmtUser->execute();
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    $user_type = $user['USER_TYPE'] ?? 'USER'; // Default to USER if not found

    // 2. Build Query
    $sql = "SELECT 
    r.data_id,
    r.reason_id,
    r.reason_other,
    l.name1 as reason_name,
    r.data_name,
    r.data_receive,
    r.data_column,
    r.sql,
    CONCAT(p.HR_FNAME,'  ',p.HR_LNAME) as p_name,
    r.crt_date,
    r.success_date,
    r.want_date,
    r.data_status_id
    FROM 10985_data_report r
    LEFT JOIN hr_person p on r.crt_by=p.id
    LEFT JOIN 10985_lookup l on r.reason_id = l.code AND l.module_name = 'datareport' AND l.table_name = 'reason_type'
    WHERE 1=1 ";

    // 3. Apply Filter if NOT SUPER
    if (strtoupper($user_type) !== 'SUPER') {
        $sql .= " AND r.crt_by = :crt_by ";
    }

    $sql .= " ORDER BY r.data_id DESC";

    $stmt = $pdo3->prepare($sql);

    // Bind crt_by only if filtered
    if (strtoupper($user_type) !== 'SUPER') {
        $stmt->bindParam(":crt_by", $user_id);
    }

    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ส่งข้อมูลออกไปเป็น JSON
    echo json_encode([
        'status' => 'success',
        'is_super' => (strtoupper($user_type) === 'SUPER'),
        'data' => $data
    ]);
} catch (PDOException $e) {
    http_response_code(500); // แจ้งว่าเป็น Server Error
    echo json_encode([
        'status' => 'error',
        'message' => 'Error fetching report: ' . $e->getMessage()
    ]);
}
