<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';

$userData = authGuard();

try {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data || !is_array($data)) {
        throw new Exception("Invalid data format or empty payload.");
    }

    $importedCount = 0;
    $errors = [];

    // Columns: รหัสสินค้า(code), ชื่ออุปกรณ์(name), ประเภท(type), หน่วยนับ(unit), ราคาต่อหน่วย(price_per_unit), แจ้งเตือนขั้นต่ำ(min_alert), ยอดยกมา(balance)
    
    foreach ($data as $index => $row) {
        $rowNum = $index + 2; // +1 for 0-index, +1 for header

        $code = isset($row['รหัสสินค้า']) ? trim($row['รหัสสินค้า']) : '';
        $name = isset($row['ชื่ออุปกรณ์']) ? trim($row['ชื่ออุปกรณ์']) : '';
        $type = isset($row['ประเภท']) ? trim($row['ประเภท']) : '';
        $unit = isset($row['หน่วยนับ']) ? trim($row['หน่วยนับ']) : '';
        
        $price_per_unit = isset($row['ราคาต่อหน่วย']) ? floatval($row['ราคาต่อหน่วย']) : 0.00;
        $lot_number = isset($row['เลขLot']) ? trim($row['เลขLot']) : '';
        $min_alert = isset($row['แจ้งเตือนขั้นต่ำ']) ? intval($row['แจ้งเตือนขั้นต่ำ']) : 5;
        $balance = isset($row['ยอดยกมา']) ? intval($row['ยอดยกมา']) : 0;

        if (empty($code) || empty($name)) {
            $errors[] = "แถวที่ $rowNum: รหัสสินค้าและชื่ออุปกรณ์ห้ามว่าง";
            continue;
        }

        // Check if exists
        $checkStmt = $pdo2->prepare("SELECT id FROM med_materials WHERE code = :code");
        $checkStmt->execute([':code' => $code]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            // Update
            $stmt = $pdo2->prepare("UPDATE med_materials 
                SET name = :name, type = :type, unit = :unit, price_per_unit = :price_per_unit, min_alert = :min_alert
                WHERE code = :code");
            $stmt->execute([
                ':name' => $name,
                ':type' => $type,
                ':unit' => $unit,
                ':price_per_unit' => $price_per_unit,
                ':min_alert' => $min_alert,
                ':code' => $code
            ]);
            // Do not update balance for existing items to prevent breaking history
        } else {
            // Insert
            $stmt = $pdo2->prepare("INSERT INTO med_materials (code, name, type, unit, price_per_unit, min_alert, balance) 
                VALUES (:code, :name, :type, :unit, :price_per_unit, :min_alert, :balance)");
            $stmt->execute([
                ':code' => $code,
                ':name' => $name,
                ':type' => $type,
                ':unit' => $unit,
                ':price_per_unit' => $price_per_unit,
                ':min_alert' => $min_alert,
                ':balance' => $balance
            ]);
            $new_material_id = $pdo2->lastInsertId();

            if ($balance > 0) {
                // Insert initial lot
                $stmtLot = $pdo2->prepare("INSERT INTO med_lots (material_id, receive_date, original_qty, remaining_qty, price_per_unit, lot_number) 
                    VALUES (:mid, NOW(), :qty, :qty, :price, :lot)");
                $stmtLot->execute([
                    ':mid' => $new_material_id,
                    ':qty' => $balance,
                    ':price' => $price_per_unit,
                    ':lot' => $lot_number
                ]);
            }
        }
        $importedCount++;
    }

    echo json_encode([
        "status" => "success", 
        "message" => "นำเข้าสำเร็จ $importedCount รายการ",
        "errors" => $errors
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
