<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require __DIR__ . '/../../config.php';

try {
    $data = json_decode(file_get_contents("php://input"));

    $month = isset($_GET['month']) ? $_GET['month'] : date('n');
    $year  = isset($_GET['year']) ? $_GET['year'] : date('Y');
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    switch ($action) {
        // 1. ดึงข้อมูลตารางเวร
        case 'getDuties':
            $sql = "SELECT it.id, it.employees_id AS employee_id, it.date, it.rate_override, it.is_special, eit.name 
                FROM duties_it it 
                LEFT JOIN employees_it eit on it.employees_id = eit.id
                WHERE MONTH(it.date) = :month AND YEAR(it.date) = :year
                ORDER BY it.date ASC";

            $stmt = $pdo2->prepare($sql);

            // --- [แก้ไขตรงนี้] ใส่ค่าตัวแปรลงไปใน execute ครับ ---
            $stmt->execute([
                ':month' => $month,
                ':year'  => $year
            ]);
            // -----------------------------------------------

            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        // 2. ดึงรายชื่อพนักงาน
        case 'getEmployees':
            $sql = "SELECT id, name, phone, position, rate_holiday, rate_weekday, rate_holiday_special, rate_weekday_special, rate_parttime FROM employees_it ORDER BY name ASC";
            $stmt = $pdo2->prepare($sql);
            $stmt->execute();
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        // 2.5 อัพเดทข้อมูลพนักงาน (ตำแหน่ง, เรทค่าตอบแทน)
        case 'updateEmployee':
            if (isset($data->id)) {
                $sql = "UPDATE employees_it 
                        SET position = :position, 
                            rate_holiday = :rate_holiday, 
                            rate_weekday = :rate_weekday, 
                            rate_holiday_special = :rate_holiday_special,
                            rate_weekday_special = :rate_weekday_special,
                            rate_parttime = :rate_parttime 
                        WHERE id = :id";
                $stmt = $pdo2->prepare($sql);
                $result = $stmt->execute([
                    ':position' => isset($data->position) ? $data->position : '',
                    ':rate_holiday' => isset($data->rate_holiday) ? (int)$data->rate_holiday : 0,
                    ':rate_weekday' => isset($data->rate_weekday) ? (int)$data->rate_weekday : 0,
                    ':rate_holiday_special' => isset($data->rate_holiday_special) ? (int)$data->rate_holiday_special : null,
                    ':rate_weekday_special' => isset($data->rate_weekday_special) ? (int)$data->rate_weekday_special : null,
                    ':rate_parttime' => isset($data->rate_parttime) ? (int)$data->rate_parttime : 0,
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

        // 3. บันทึกการแก้ไข
        case 'updateDuty':
            if (isset($data->id) && isset($data->employee_id) && isset($data->date)) {
                $sql = "UPDATE duties_it SET employees_id = :eid, date = :dte, rate_override = :rate, is_special = :is_special WHERE id = :id";
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

        // 4. ลบข้อมูล
        case 'deleteDuty':
            if (isset($data->id)) {
                $sql = "DELETE FROM duties_it WHERE id = :id";
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
