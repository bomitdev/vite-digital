<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

require '../config.php';

// Validate and sanitize input parameters
$year = isset($_GET['year']) ? filter_var($_GET['year'], FILTER_VALIDATE_INT) : 2025;
$month = isset($_GET['month']) ? filter_var($_GET['month'], FILTER_VALIDATE_INT) : 4;

if ($year === false || $month === false || $month < 1 || $month > 12) {
    echo json_encode(["error" => "Invalid year or month"]);
    exit;
}

try {
    // Create a new PDO instance and set error mode to exception
    $pdo2 = new PDO("mysql:host={$db2['host']};dbname={$db2['name']};charset=utf8", $db2['user'], $db2['pass']);
    $pdo2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare the SQL statement
    $stmt1 = $pdo2->prepare("
        SELECT 
            e.id,
            e.name, 
            e.phone, 
            MAX(IF(DAY(d.date)=1, 'IT', '')) AS d1,
            MAX(IF(DAY(d.date)=2, 'IT', '')) AS d2,
            MAX(IF(DAY(d.date)=3, 'IT', '')) AS d3,
            MAX(IF(DAY(d.date)=4, 'IT', '')) AS d4,
            MAX(IF(DAY(d.date)=5, 'IT', '')) AS d5,
            MAX(IF(DAY(d.date)=6, 'IT', '')) AS d6,
            MAX(IF(DAY(d.date)=7, 'IT', '')) AS d7,
            MAX(IF(DAY(d.date)=8, 'IT', '')) AS d8,
            MAX(IF(DAY(d.date)=9, 'IT', '')) AS d9,
            MAX(IF(DAY(d.date)=10, 'IT', '')) AS d10,
            MAX(IF(DAY(d.date)=11, 'IT', '')) AS d11,
            MAX(IF(DAY(d.date)=12, 'IT', '')) AS d12,
            MAX(IF(DAY(d.date)=13, 'IT', '')) AS d13,
            MAX(IF(DAY(d.date)=14, 'IT', '')) AS d14,
            MAX(IF(DAY(d.date)=15, 'IT', '')) AS d15,
            MAX(IF(DAY(d.date)=16, 'IT', '')) AS d16,
            MAX(IF(DAY(d.date)=17, 'IT', '')) AS d17,
            MAX(IF(DAY(d.date)=18, 'IT', '')) AS d18,
            MAX(IF(DAY(d.date)=19, 'IT', '')) AS d19,
            MAX(IF(DAY(d.date)=20, 'IT', '')) AS d20,
            MAX(IF(DAY(d.date)=21, 'IT', '')) AS d21,
            MAX(IF(DAY(d.date)=22, 'IT', '')) AS d22,
            MAX(IF(DAY(d.date)=23, 'IT', '')) AS d23,
            MAX(IF(DAY(d.date)=24, 'IT', '')) AS d24,
            MAX(IF(DAY(d.date)=25, 'IT', '')) AS d25,
            MAX(IF(DAY(d.date)=26, 'IT', '')) AS d26,
            MAX(IF(DAY(d.date)=27, 'IT', '')) AS d27,
            MAX(IF(DAY(d.date)=28, 'IT', '')) AS d28,
            MAX(IF(DAY(d.date)=29, 'IT', '')) AS d29,
            MAX(IF(DAY(d.date)=30, 'IT', '')) AS d30,
            MAX(IF(DAY(d.date)=31, 'IT', '')) AS d31
        FROM employees e 
        LEFT JOIN duties d ON e.id = d.employee_id 
        WHERE  YEAR(d.date) = :year 
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

    // Output the data in JSON format
    echo json_encode([
        "year" => $year,
        "month" => $month,
        "count" => count($data),
        "data" => $data
    ]);

} catch (PDOException $e) {
    // Log the error to a file (เฉพาะตอน debug เท่านั้น)
    error_log($e->getMessage(), 3, __DIR__ . '/error.log');

    // Return a 500 Internal Server Error status code
    http_response_code(500);
    echo json_encode(["error" => "An internal server error occurred."]);
}
