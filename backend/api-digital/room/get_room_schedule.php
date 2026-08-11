<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

require_once '../../config.php';

try {
    $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
    $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');

    $sql = "
        SELECT 
            r.ID,
            r.ROOM_ID,
            r.DATE_BEGIN,
            r.TIME_BEGIN,
            r.DATE_END,
            r.TIME_END,
            r.PERSON_REQUEST_NAME,
            r.PERSON_REQUEST_DEP,
            r.SERVICE_STORY,
            r.TOTAL_PEOPLE,
            r.STATUS,
            r.APP_TYPE_SAVE,
            i.room_name
        FROM room_service r
        LEFT JOIN room_index i ON r.ROOM_ID = i.room_id
        WHERE (
            (YEAR(r.DATE_BEGIN) = :year AND MONTH(r.DATE_BEGIN) = :month)
            OR (YEAR(r.DATE_END) = :year_end AND MONTH(r.DATE_END) = :month_end)
        )
        AND r.STATUS != 'CANCEL'
        ORDER BY r.DATE_BEGIN ASC, r.TIME_BEGIN ASC
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
        'message' => $e->getMessage()
    ]);
}
