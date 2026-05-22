<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require '../config.php';

// รับข้อมูล JSON
$data = json_decode(file_get_contents("php://input"));

if (
    !empty($data->data_id) &&
    !empty($data->data_name)
) {
    $data_id = $data->data_id;
    $data_name = $data->data_name;
    $data_column = $data->data_column ?? '';
    $reason_id = $data->reason_id ?? 1;
    $reason_other = $data->reason_other ?? '';
    $data_type_id = $data->data_type_id ?? 'รายครั้ง';
    $data_date = $data->data_date ?? '';
    $file_type = $data->file_type ?? 'Excel';
    $data_receive = $data->data_receive ?? 'ส่งผ่าน Email/Line';
    $want_date = $data->want_date ?? NULL;
    $remark = $data->remark ?? '';

    require_once __DIR__ . '/../auth_utils.php';

    // Secure Auth
    $userData = authGuard();
    $upd_by = $userData['uid'];

    // Current Time
    $upd_date = date("Y-m-d H:i:s");

    try {
        $sql = "UPDATE 10985_data_report SET
                data_name = :data_name,
                data_column = :data_column,
                reason_id = :reason_id,
                reason_other = :reason_other,
                data_type_id = :data_type_id,
                data_date = :data_date,
                file_type = :file_type,
                data_receive = :data_receive,
                want_date = :want_date,
                remark = :remark,
                upd_by = :upd_by,
                upd_date = :upd_date
                WHERE data_id = :data_id";

        $stmt = $pdo3->prepare($sql);

        // Bind parameters
        $stmt->bindParam(":data_name", $data_name);
        $stmt->bindParam(":data_column", $data_column);
        $stmt->bindParam(":reason_id", $reason_id);
        $stmt->bindParam(":reason_other", $reason_other);
        $stmt->bindParam(":data_type_id", $data_type_id);
        $stmt->bindParam(":data_date", $data_date);
        $stmt->bindParam(":file_type", $file_type);
        $stmt->bindParam(":data_receive", $data_receive);
        $stmt->bindParam(":want_date", $want_date);
        $stmt->bindParam(":remark", $remark);
        $stmt->bindParam(":upd_by", $upd_by);
        $stmt->bindParam(":upd_date", $upd_date);
        $stmt->bindParam(":data_id", $data_id);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "อัปเดตข้อมูลสำเร็จ"]);
        } else {
            echo json_encode(["status" => "error", "message" => "ไม่สามารถอัปเดตข้อมูลได้"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "ข้อมูลไม่ครบถ้วน"]);
}
