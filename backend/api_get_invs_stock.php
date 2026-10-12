<?php
// ไฟล์: api_get_invs_stock.php
// หน้าที่: ส่งออกข้อมูลตั้งต้นคลังวัสดุจากตาราง MySQL เป็น JSON ให้ Vue.js นำไปแสดงผล
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // อนุญาตให้ Vue (คนละ Port) เรียกใช้ได้

// นำเข้าไฟล์ตั้งค่าการเชื่อมต่อ (ถ้ามี) หรือเขียนเชื่อมต่อตรง
$mySqlHost = "localhost";
$mySqlDb   = "digital";
$mySqlUser = "root";
$mySqlPass = "";

try {
    $conn = new PDO("mysql:host=$mySqlHost;dbname=$mySqlDb;charset=utf8", $mySqlUser, $mySqlPass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // รับพารามิเตอร์เพื่อกรองข้อมูล (เช่น ค้นหาด้วยชื่อ)
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    $query = "SELECT working_code, drug_name, last_pack_ratio, rm_qty, rm_value, updated_at 
              FROM invs_monthly_stock";
    $params = [];

    if (!empty($search)) {
        $query .= " WHERE drug_name LIKE :search OR working_code LIKE :search";
        $params[':search'] = "%$search%";
    }
    
    // เรียงตามชื่อยา
    $query .= " ORDER BY drug_name ASC";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "total" => count($data),
        "data" => $data
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
