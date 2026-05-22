<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

// Validate and sanitize input parameters
$year = isset($_GET['year']) ? filter_var($_GET['year'], FILTER_VALIDATE_INT) : 2025;
$month = isset($_GET['month']) ? filter_var($_GET['month'], FILTER_VALIDATE_INT) : 4;

if ($year === false || $month === false || $month < 1 || $month > 12) {
    echo json_encode(["error" => "Invalid year or month"]);
    exit;
}

try {

    // Prepare the SQL statement
    $stmt1 = $pdo2->prepare("SELECT 
            e.id,
            e.name, 
            e.phone, 
            MAX(IF(DAY(d.date)=1, 'X', '')) AS d1,
            MAX(IF(DAY(d.date)=2, 'X', '')) AS d2,
            MAX(IF(DAY(d.date)=3, 'X', '')) AS d3,
            MAX(IF(DAY(d.date)=4, 'X', '')) AS d4,
            MAX(IF(DAY(d.date)=5, 'X', '')) AS d5,
            MAX(IF(DAY(d.date)=6, 'X', '')) AS d6,
            MAX(IF(DAY(d.date)=7, 'X', '')) AS d7,
            MAX(IF(DAY(d.date)=8, 'X', '')) AS d8,
            MAX(IF(DAY(d.date)=9, 'X', '')) AS d9,
            MAX(IF(DAY(d.date)=10, 'X', '')) AS d10,
            MAX(IF(DAY(d.date)=11, 'X', '')) AS d11,
            MAX(IF(DAY(d.date)=12, 'X', '')) AS d12,
            MAX(IF(DAY(d.date)=13, 'X', '')) AS d13,
            MAX(IF(DAY(d.date)=14, 'X', '')) AS d14,
            MAX(IF(DAY(d.date)=15, 'X', '')) AS d15,
            MAX(IF(DAY(d.date)=16, 'X', '')) AS d16,
            MAX(IF(DAY(d.date)=17, 'X', '')) AS d17,
            MAX(IF(DAY(d.date)=18, 'X', '')) AS d18,
            MAX(IF(DAY(d.date)=19, 'X', '')) AS d19,
            MAX(IF(DAY(d.date)=20, 'X', '')) AS d20,
            MAX(IF(DAY(d.date)=21, 'X', '')) AS d21,
            MAX(IF(DAY(d.date)=22, 'X', '')) AS d22,
            MAX(IF(DAY(d.date)=23, 'X', '')) AS d23,
            MAX(IF(DAY(d.date)=24, 'X', '')) AS d24,
            MAX(IF(DAY(d.date)=25, 'X', '')) AS d25,
            MAX(IF(DAY(d.date)=26, 'X', '')) AS d26,
            MAX(IF(DAY(d.date)=27, 'X', '')) AS d27,
            MAX(IF(DAY(d.date)=28, 'X', '')) AS d28,
            MAX(IF(DAY(d.date)=29, 'X', '')) AS d29,
            MAX(IF(DAY(d.date)=30, 'X', '')) AS d30,
            MAX(IF(DAY(d.date)=31, 'X', '')) AS d31
        FROM employees_opdcard e 
        LEFT JOIN duties_opdcard d ON e.id=d.employees_opdcard_id
        WHERE YEAR(d.date) = :year 
          AND MONTH(d.date) = :month
        GROUP BY e.id
    ");

    // Bind parameters
    $stmt1->bindParam(':year', $year, PDO::PARAM_INT);
    $stmt1->bindParam(':month', $month, PDO::PARAM_INT);

    // Execute the statement
    $stmt1->execute();

    // Fetch all results
    $data = $stmt1->fetchAll(PDO::FETCH_ASSOC);  

    // Output JSON
    echo json_encode([
        "year" => $year,
        "month" => $month,
        "count" => count($data),
        "data" => $data
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "An internal server error occurred."]);
    error_log("Database error: " . $e->getMessage());
}
