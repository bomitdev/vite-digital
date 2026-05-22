<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . '/../../config.php';

try {
    $data = json_decode(file_get_contents("php://input"));

    $month = isset($_GET['month']) ? $_GET['month'] : date('n');
    $year  = isset($_GET['year']) ? $_GET['year'] : date('Y');
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    switch ($action) {
        case 'getDuties':

            $sql = "SELECT 
                        c.id, 
                        c.date, 
                        c.rate_override,
                        c.is_special,
                        c.employees_claim_id AS employee_id, 
                        ec.name 
                    FROM duties_claim c
                    LEFT JOIN employees_claim ec ON c.employees_claim_id = ec.id 
                    WHERE MONTH(c.date) = :month 
                    AND YEAR(c.date) = :year
                    ORDER BY c.date ASC";

            $stmt = $pdo2->prepare($sql);
            $stmt->bindParam(':month', $month, PDO::PARAM_INT);
            $stmt->bindParam(':year', $year, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'getEmployees':
            $sql = "SELECT id, name, position, rate_holiday, rate_weekday, rate_parttime, rate_holiday_special, rate_weekday_special FROM employees_claim ORDER BY name ASC";
            $stmt = $pdo2->prepare($sql);
            $stmt->execute();
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'updateEmployee':
            if (isset($data->id)) {
                $sql = "UPDATE employees_claim 
                        SET position = :position, 
                            rate_holiday = :rate_holiday, 
                            rate_weekday = :rate_weekday, 
                            rate_parttime = :rate_parttime,
                            rate_holiday_special = :rate_holiday_special,
                            rate_weekday_special = :rate_weekday_special
                        WHERE id = :id";
                $stmt = $pdo2->prepare($sql);
                $result = $stmt->execute([
                    ':position' => isset($data->position) ? $data->position : '',
                    ':rate_holiday' => isset($data->rate_holiday) ? (int)$data->rate_holiday : 0,
                    ':rate_weekday' => isset($data->rate_weekday) ? (int)$data->rate_weekday : 0,
                    ':rate_parttime' => isset($data->rate_parttime) ? (int)$data->rate_parttime : 0,
                    ':rate_holiday_special' => isset($data->rate_holiday_special) ? (int)$data->rate_holiday_special : 0,
                    ':rate_weekday_special' => isset($data->rate_weekday_special) ? (int)$data->rate_weekday_special : 0,
                    ':id' => $data->id
                ]);

                if ($result) {
                    echo json_encode(['message' => 'Update employee successful']);
                } else {
                    echo json_encode(['message' => 'Update employee failed']);
                }
            } else {
                echo json_encode(['message' => 'Incomplete employee data']);
            }
            break;

        case 'updateDuty':
            if (isset($data->id) && isset($data->employee_id) && isset($data->date)) {
                $sql = "UPDATE duties_claim 
                        SET employees_claim_id = :eid, date = :dte, rate_override = :rate, is_special = :is_special 
                        WHERE id = :id";

                $stmt = $pdo2->prepare($sql);
                $rate_val = null; // Force clear legacy rate_override
                $is_special = isset($data->is_special) ? (int)$data->is_special : 0;
                $result = $stmt->execute([
                    ':eid' => $data->employee_id,
                    ':dte' => $data->date,
                    ':rate' => $rate_val,
                    ':is_special' => $is_special,
                    ':id'  => $data->id
                ]);

                if ($result) {
                    echo json_encode(['message' => 'Update successful']);
                } else {
                    echo json_encode(['message' => 'Update failed']);
                }
            } else {
                echo json_encode(['message' => 'Incomplete data']);
            }
            break;

        case 'deleteDuty':
            if (isset($data->id)) {
                $sql = "DELETE FROM duties_claim WHERE id = :id";
                $stmt = $pdo2->prepare($sql);
                $result = $stmt->execute([':id' => $data->id]);

                if ($result) {
                    echo json_encode(['message' => 'Delete successful']);
                } else {
                    echo json_encode(['message' => 'Delete failed']);
                }
            } else {
                echo json_encode(['message' => 'Incomplete data']);
            }
            break;

        default:
            echo json_encode(['message' => 'Invalid Action']);
    }
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => 'Error: ' . $e->getMessage()
    ];
    echo json_encode($response);
}
