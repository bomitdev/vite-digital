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

    foreach ($data as $index => $row) {
        $rowNum = $index + 2;

        $cleanRow = [];
        foreach ($row as $k => $v) {
            $cleanRow[trim((string)$k)] = $v;
        }

        $code = isset($cleanRow['รหัสสินค้า']) ? trim((string)$cleanRow['รหัสสินค้า']) : '';
        $name = isset($cleanRow['ชื่ออุปกรณ์']) ? trim((string)$cleanRow['ชื่ออุปกรณ์']) : '';
        $type = isset($cleanRow['ประเภท']) ? trim((string)$cleanRow['ประเภท']) : '';
        $unit = isset($cleanRow['หน่วยนับ']) ? trim((string)$cleanRow['หน่วยนับ']) : '';
        
        $price_per_unit = isset($cleanRow['ราคาต่อหน่วย']) && $cleanRow['ราคาต่อหน่วย'] !== '' ? floatval($cleanRow['ราคาต่อหน่วย']) : 0.00;
        $lot_number = isset($cleanRow['เลขLot']) ? trim((string)$cleanRow['เลขLot']) : '';
        $min_alert = isset($cleanRow['แจ้งเตือนขั้นต่ำ']) && $cleanRow['แจ้งเตือนขั้นต่ำ'] !== '' ? intval($cleanRow['แจ้งเตือนขั้นต่ำ']) : 5;
        $balance = isset($cleanRow['ยอดยกมา']) && $cleanRow['ยอดยกมา'] !== '' ? intval($cleanRow['ยอดยกมา']) : 0;

        if (empty($code) || empty($name)) {
            $errors[] = "แถวที่ $rowNum: รหัสสินค้าและชื่ออุปกรณ์ห้ามว่าง";
            continue;
        }

        $checkStmt = $pdo2->prepare("SELECT id FROM mt_it_materials WHERE code = :code");
        $checkStmt->execute([':code' => $code]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            $stmt = $pdo2->prepare("UPDATE mt_it_materials 
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
        } else {
            $stmt = $pdo2->prepare("INSERT INTO mt_it_materials (code, name, type, unit, price_per_unit, min_alert, balance, is_active) 
                VALUES (:code, :name, :type, :unit, :price_per_unit, :min_alert, :balance, 1)");
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
                $finalLotNo = !empty($lot_number) ? $lot_number : 'BF-IT-69';
                $stmtLot = $pdo2->prepare("INSERT INTO mt_it_lots 
                    (material_id, lot_number, vendor, price_per_unit, original_qty, remaining_qty, receive_date) 
                    VALUES (:mid, :lot, 'ยอดยกมา', :price, :qty1, :qty2, '2026-09-30 23:59:59')");
                $stmtLot->execute([
                    ':mid' => $new_material_id,
                    ':lot' => $finalLotNo,
                    ':price' => $price_per_unit,
                    ':qty1' => $balance,
                    ':qty2' => $balance
                ]);

                $stmtTx = $pdo2->prepare("INSERT INTO mt_it_transactions 
                    (material_id, action_type, quantity, total_price, action_date, user_profile_name, receiver_name, reference_dest, note)
                    VALUES (:mid, 'IN', :qty, :total_price, '2026-09-30 23:59:59', :user, 'ยอดยกมา', 'ยอดยกมาตั้งต้น', 'ยอดยกมาต้นปีงบประมาณ 2569 (BF-IT-69)')");
                $stmtTx->execute([
                    ':mid' => $new_material_id,
                    ':qty' => $balance,
                    ':total_price' => $balance * $price_per_unit,
                    ':user' => $userData['name'] ?? $userData['user'] ?? 'System Import'
                ]);
            }
        }

        if (!empty($type)) {
            $catCheck = $pdo2->prepare("SELECT id FROM mt_it_categories WHERE name = ?");
            $catCheck->execute([$type]);
            if (!$catCheck->fetch()) {
                $prefix = '';
                if (preg_match('/^([A-Za-z]+)-/i', $code, $m)) {
                    $prefix = strtoupper($m[1]);
                } else if (preg_match('/[A-Za-z]{2,}/', $type, $m)) {
                    $prefix = strtoupper(substr($m[0], 0, 3));
                } else {
                    $prefix = 'IT';
                }
                $catIns = $pdo2->prepare("INSERT IGNORE INTO mt_it_categories (name, prefix) VALUES (?, ?)");
                $catIns->execute([$type, $prefix]);
            }
        }

        $importedCount++;
    }

    echo json_encode([
        'status' => 'success',
        'message' => "นำเข้าสำเร็จ $importedCount รายการ" . (count($errors) > 0 ? " (มีข้อผิดพลาด " . count($errors) . " รายการ)" : ""),
        'imported_count' => $importedCount,
        'errors' => $errors
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
