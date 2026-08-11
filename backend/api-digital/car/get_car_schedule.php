<?php
header('Content-Type: application/json');

require_once '../../config.php';

try {
    $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
    $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');

    $sql = "
        SELECT 
            r.RESERVE_ID,
            r.RESERVE_BEGIN_DATE,
            r.RESERVE_BEGIN_TIME,
            r.RESERVE_END_DATE,
            r.RESERVE_END_TIME,
            r.RESERVE_PERSON_NAME,
            r.RESERVE_NAME,
            r.CAR_DRIVER_NAME,
            r.CAR_DRIVER_SET_NAME,
            (SELECT GROUP_CONCAT(p.HR_FULLNAME SEPARATOR ', ') FROM car_index_person p WHERE p.RESERVE_ID = r.RESERVE_ID) as PASSENGERS,
            cl.LOCATION_NAME,
            r.APPOINT_LOCATE_NAME,
            r.STATUS,
            r.PRO_NAME,
            c.CAR_REG,
            c.CAR_BRAND_ID,
            c.CAR_COLOR
        FROM car_reserve r
        LEFT JOIN car_index c ON r.CAR_SET_ID = c.CAR_ID
        LEFT JOIN car_location cl ON r.RESERVE_LOCATION_ID = cl.LOCATION_ID
        WHERE (
            (YEAR(r.RESERVE_BEGIN_DATE) = :year AND MONTH(r.RESERVE_BEGIN_DATE) = :month)
            OR (YEAR(r.RESERVE_END_DATE) = :year_end AND MONTH(r.RESERVE_END_DATE) = :month_end)
        )
        AND r.STATUS != 'CANCEL'
        ORDER BY r.RESERVE_BEGIN_DATE ASC, r.RESERVE_BEGIN_TIME ASC
    ";

    $stmt = $pdo3->prepare($sql);
    $stmt->execute([
        ':year' => $year,
        ':month' => $month,
        ':year_end' => $year,
        ':month_end' => $month
    ]);

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $data
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error fetching car schedule: ' . $e->getMessage()
    ]);
}
