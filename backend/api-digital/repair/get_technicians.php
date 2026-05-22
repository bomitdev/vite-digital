<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

try {
    $sql = "SELECT 
                p.ID,
                CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as FULLNAME
            FROM hr_person p
            LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
            LEFT JOIN hr_position pos ON p.HR_POSITION_ID = pos.HR_POSITION_ID
            WHERE p.HR_STATUS_ID = '01' 
              AND pos.HR_POSITION_NAME LIKE '%นักวิชาการคอมพิวเตอร์%'
            ORDER BY p.HR_FNAME ASC";

    $stmt = $pdo3->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $data]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
