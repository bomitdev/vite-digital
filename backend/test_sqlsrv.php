<?php
// ไฟล์นี้ใช้สำหรับทดสอบการเชื่อมต่อฐานข้อมูล SQL Server
header('Content-Type: text/html; charset=utf-8');

// === 1. ใส่ข้อมูลการเชื่อมต่อ SQL Server (ระบบ invs เดิม) ตรงนี้ ===
$serverName = "192.168.2.19, 1433"; // เช่น "192.168.1.100" หรือ "localhost"
$database   = "invs";
$username   = "sa"; // username ของ sql server
$password   = "Cnm@10985";

echo "<h2>ทดสอบการเชื่อมต่อ SQL Server</h2>";

// ตรวจสอบว่า Driver ถูกโหลดมาหรือยัง
if (!extension_loaded('sqlsrv') && !extension_loaded('pdo_sqlsrv')) {
    echo "<h3 style='color:red;'>❌ ยังไม่ได้โหลด Driver (sqlsrv หรือ pdo_sqlsrv)</h3>";
    echo "กรุณาตรวจสอบว่าก็อปปี้ไฟล์ .dll ไปที่ C:/xampp/php/ext/ แล้วแก้ไข php.ini และ Restart Apache แล้วหรือยัง<br><br>";

    echo "<b>Extension ที่โหลดในระบบตอนนี้:</b><br>";
    echo "<pre>";
    print_r(get_loaded_extensions());
    echo "</pre>";
    exit;
} else {
    echo "<h3 style='color:green;'>✅ โหลด Driver sqlsrv สำเร็จแล้ว!</h3>";
}

// ทดสอบเชื่อมต่อด้วย PDO
try {
    $conn = new PDO("sqlsrv:server=$serverName;Database=$database", $username, $password);
    // ตั้งค่าให้แสดง Error หากมีปัญหา
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h3 style='color:green;'>🎉 เชื่อมต่อฐานข้อมูล $database สำเร็จ!</h3>";

    // หากต้องการทดสอบคิวรี่ข้อมูล ลองเอาคอมเมนต์ด้านล่างออกแล้วใส่ชื่อตารางของคุณดูครับ
    /*
    $stmt = $conn->query("SELECT TOP 5 * FROM ชื่อตาราง");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($results);
    echo "</pre>";
    */
} catch (PDOException $e) {
    echo "<h3 style='color:red;'>❌ เชื่อมต่อไม่สำเร็จ:</h3>";
    echo "<b>สาเหตุ:</b> " . $e->getMessage();
}
