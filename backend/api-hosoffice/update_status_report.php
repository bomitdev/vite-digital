<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth_utils.php';

// รับข้อมูล JSON
$data = json_decode(file_get_contents("php://input"));

if (
    !empty($data->data_id) &&
    !empty($data->data_status_id)
) {
    $data_id = $data->data_id;
    $data_status_id = $data->data_status_id;

    // Secure Auth
    $userData = authGuard();
    $upd_by = $userData['uid'];

    // Current Time
    $upd_date = date("Y-m-d H:i:s");
    $success_date = ($data_status_id == 3) ? date("Y-m-d H:i:s") : NULL; // ถ้าสถานะคือ ดำเนินการเรียบร้อย (3) ให้ลงวันที่สำเร็จ

    $linked_report_id = isset($data->linked_report_id) && $data->linked_report_id !== '' ? $data->linked_report_id : NULL;

    try {
        if ($success_date) {
            $sql = "UPDATE 10985_data_report SET
                    data_status_id = :data_status_id,
                    success_date = :success_date,
                    `sql` = :sql_text,
                    linked_report_id = :linked_report_id,
                    upd_by = :upd_by,
                    upd_date = :upd_date
                    WHERE data_id = :data_id";
        } else {
            $sql = "UPDATE 10985_data_report SET
                    data_status_id = :data_status_id,
                    `sql` = :sql_text,
                    linked_report_id = :linked_report_id,
                    upd_by = :upd_by,
                    upd_date = :upd_date
                    WHERE data_id = :data_id";
        }

        $stmt = $pdo3->prepare($sql);

        // Bind parameters
        $stmt->bindParam(":data_status_id", $data_status_id);
        if ($success_date) {
            $stmt->bindParam(":success_date", $success_date);
        }
        $stmt->bindParam(":sql_text", $data->sql); // Bind SQL text
        $stmt->bindParam(":linked_report_id", $linked_report_id, PDO::PARAM_INT);
        $stmt->bindParam(":upd_by", $upd_by);
        $stmt->bindParam(":upd_date", $upd_date);
        $stmt->bindParam(":data_id", $data_id);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "อัปเดตสถานะสำเร็จ"]);
        } else {
            echo json_encode(["status" => "error", "message" => "ไม่สามารถอัปเดตสถานะได้"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "ข้อมูลไม่ครบถ้วน"]);
}
