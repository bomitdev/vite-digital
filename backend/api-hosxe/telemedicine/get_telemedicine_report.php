<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

require '../../config.php';

try {
    /** @var PDO $pdo1 */
    $pdo1->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '2024-10-01';
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '2025-09-30';

    // Query Telemedicine count grouped by specialty
    $stmt = $pdo1->prepare("
        SELECT 
            COALESCE(s.name, 'ไม่ระบุ') AS department_name, 
            COUNT(DISTINCT CASE WHEN o.vstdate BETWEEN :start_date AND :end_date THEN o.vn END) AS total,
            COUNT(DISTINCT CASE WHEN o.vstdate = CURDATE() THEN o.vn END) AS today_count,
            COUNT(DISTINCT CASE WHEN o.vstdate = CURDATE() - INTERVAL 1 DAY THEN o.vn END) AS yesterday_count
        FROM ovst o 
        LEFT JOIN ovstist i ON o.ovstist = i.ovstist 
        LEFT JOIN spclty s ON o.spclty = s.spclty 
        WHERE (o.vstdate BETWEEN :start_date AND :end_date OR o.vstdate = CURDATE() OR o.vstdate = CURDATE() - INTERVAL 1 DAY)
          AND i.export_code = '5' 
        GROUP BY s.name 
        HAVING total > 0 OR today_count > 0 OR yesterday_count > 0
        ORDER BY total DESC, today_count DESC
    ");

    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get today count
    $stmt_today = $pdo1->prepare("
        SELECT COUNT(DISTINCT o.vn) AS total 
        FROM ovst o 
        LEFT JOIN ovstist i ON o.ovstist = i.ovstist 
        WHERE o.vstdate = CURDATE() AND i.export_code = '5'
    ");
    $stmt_today->execute();
    $today_count = $stmt_today->fetchColumn() ?: 0;

    // Get yesterday count
    $stmt_yesterday = $pdo1->prepare("
        SELECT COUNT(DISTINCT o.vn) AS total 
        FROM ovst o 
        LEFT JOIN ovstist i ON o.ovstist = i.ovstist 
        WHERE o.vstdate = CURDATE() - INTERVAL 1 DAY AND i.export_code = '5'
    ");
    $stmt_yesterday->execute();
    $yesterday_count = $stmt_yesterday->fetchColumn() ?: 0;

    echo json_encode([
        "status" => "success", 
        "data" => $data,
        "today" => $today_count,
        "yesterday" => $yesterday_count
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
