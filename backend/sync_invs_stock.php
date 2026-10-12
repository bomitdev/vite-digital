<?php
// ไฟล์: sync_invs_stock.php
// หน้าที่: ดึงข้อมูลสรุปสต๊อกรายเดือนจาก SQL Server (INVS) มาเก็บไว้ใน MySQL (digital)
header('Content-Type: application/json; charset=utf-8');

// === 1. ตั้งค่าการเชื่อมต่อฐานข้อมูล ===
// SQL Server (ต้นทาง)
$sqlSrvHost = "192.168.2.19, 1433";
$sqlSrvDb   = "invs";
$sqlSrvUser = "sa";
$sqlSrvPass = "Cnm@10985";

// MySQL (ปลายทาง - digital)
$mySqlHost = "localhost";
$mySqlDb   = "digital";
$mySqlUser = "root";
$mySqlPass = ""; // รหัสผ่าน MySQL ของ XAMPP (มักจะว่างไว้)

// รับค่าเดือนและปีที่ต้องการซิงค์ (ค่าเริ่มต้นคือเดือนปัจจุบัน YYYYMM)
$targetMonth = isset($_GET['month']) ? $_GET['month'] : date('Ym'); 

try {
    // เชื่อมต่อ SQL Server
    $connSqlSrv = new PDO("sqlsrv:server=$sqlSrvHost;Database=$sqlSrvDb", $sqlSrvUser, $sqlSrvPass);
    $connSqlSrv->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // เชื่อมต่อ MySQL
    $connMySql = new PDO("mysql:host=$mySqlHost;dbname=$mySqlDb;charset=utf8", $mySqlUser, $mySqlPass);
    $connMySql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // === 2. สร้างตารางรองรับข้อมูลใน MySQL (ถ้ายังไม่มี) ===
    $createTableQuery = "
    CREATE TABLE IF NOT EXISTS invs_monthly_stock (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `year_month` VARCHAR(6) NOT NULL,
        `working_code` VARCHAR(50) NOT NULL,
        `drug_name` VARCHAR(255),
        `last_pack_ratio` VARCHAR(50),
        `fw_qty` FLOAT DEFAULT 0,
        `fw_value` FLOAT DEFAULT 0,
        `rcv_qty` FLOAT DEFAULT 0,
        `rcv_value` FLOAT DEFAULT 0,
        `dis_qty` FLOAT DEFAULT 0,
        `dis_value` FLOAT DEFAULT 0,
        `rm_qty` FLOAT DEFAULT 0,
        `rm_value` FLOAT DEFAULT 0,
        `diff_x_value` FLOAT DEFAULT 0,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_stock (`year_month`, `working_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $connMySql->exec($createTableQuery);

    // === 3. Query ดึงข้อมูลจาก SQL Server ===
    // (ใช้ Query ที่คุณให้มา แต่เปลี่ยน 202312 เป็นตัวแปร $targetMonth)
    $querySqlSrv = "
    select *,(isnull(T.FW_VALUE,0)+isnull(T.RCV_VALUE,0)-isnull(T.DIS_VALUE,0)) as 'X' from (  
    select i.WORKING_CODE,g.DRUG_NAME, i.LAST_PACK_RATIO,
    (select top 1 c.REMAIN_QTY from  CARD c  (NOLOCK)        
            where 
            left(c.R_S_DATE,6) <= :month1
            and c.WORKING_CODE=i.WORKING_CODE 
            and c.STOCK_ID=i.DEPT_ID order by c.OPERATE_DATE DESC ) as FW_QTY,
    (select top 1 c.REMAIN_VALUE from  CARD c  (NOLOCK)        
            where 
            left(c.R_S_DATE,6) <= :month2
            and c.WORKING_CODE=i.WORKING_CODE 
            and c.STOCK_ID=i.DEPT_ID order by c.OPERATE_DATE DESC ) as FW_VALUE,         
    Cx.RCV_QTY,Cx.RCV_VALUE,Cx.DIS_QTY ,Cx.DIS_VALUE,
    isnull((select top 1 c.REMAIN_QTY from  CARD c (NOLOCK)       
            where 
                    left(c.R_S_DATE,6) between left(dbo.ce2cymd(GETDATE()-300),6) and :month3
            and c.WORKING_CODE=i.WORKING_CODE 
            and c.STOCK_ID=i.DEPT_ID order by c.OPERATE_DATE DESC ),0) as RM_QTY,i.QTY_ON_HAND,
    (select top 1 c.REMAIN_VALUE from  CARD c (NOLOCK)       
            where 
            left(c.R_S_DATE,6) <= :month4
            and c.WORKING_CODE=i.WORKING_CODE 
            and c.STOCK_ID=i.DEPT_ID order by c.OPERATE_DATE DESC ) as RM_VALUE            
    from INV_MD  i (NOLOCK)     
    left join DRUG_GN g (NOLOCK) on (i.WORKING_CODE=g.WORKING_CODE)
    left join  (select c1.WORKING_CODE,c1.STOCK_ID,
    SUM(CASE when c1.R_S_STATUS in ('R','I','G','A') then c1.ACTIVE_QTY 
             when c1.R_S_STATUS in ('B') then c1.ACTIVE_QTY*-1 else NULL end) as RCV_QTY,
    SUM(CASE when c1.R_S_STATUS in ('R','I','G','A')  then c1.[VALUE] 
                                             when (c1.R_S_STATUS='B') then c1.[VALUE]*-1 else NULL end) as RCV_VALUE,                     
                            SUM(CASE when c1.R_S_STATUS in ('T','S')  then c1.ACTIVE_QTY 
                                             when (c1.R_S_STATUS='C') then c1.ACTIVE_QTY*-1 else NULL end) as DIS_QTY,
                            SUM(CASE when c1.R_S_STATUS in ('T','S')  then c1.[VALUE] 
                                             when (c1.R_S_STATUS='C') then c1.[VALUE]*-1 else NULL end) as DIS_VALUE
                            from CARD c1  (NOLOCK)  
                where  c1.STOCK_ID='STOCK2'                        
                and    left(c1.R_S_DATE,6) between :month5 and :month6
                group by c1.WORKING_CODE,c1.STOCK_ID ) as Cx on (Cx.WORKING_CODE = i.WORKING_CODE  and Cx.STOCK_ID=i.DEPT_ID)   
    where  i.DEPT_ID='STOCK2' and   g.WORKING_CODE is not null      
    ) as T
    where (isnull(T.FW_QTY,0)+isnull(T.RCV_QTY,0)+isnull(T.DIS_QTY,0)+ isnull(T.RM_QTY,0)) > 0 
    and T.RM_VALUE<>(isnull(T.FW_VALUE,0)+isnull(T.RCV_VALUE,0)-isnull(T.DIS_VALUE,0))
    ORDER BY T.DRUG_NAME
    ";

    $stmtSqlSrv = $connSqlSrv->prepare($querySqlSrv);
    // ผูกค่าตัวแปรเดือน (เพื่อให้ดึงข้อมูลเดือนไหนก็ได้แบบ Dynamic)
    $stmtSqlSrv->execute([
        ':month1' => $targetMonth,
        ':month2' => $targetMonth,
        ':month3' => $targetMonth,
        ':month4' => $targetMonth,
        ':month5' => $targetMonth,
        ':month6' => $targetMonth,
    ]);

    $data = $stmtSqlSrv->fetchAll(PDO::FETCH_ASSOC);

    if (empty($data)) {
        echo json_encode(["status" => "success", "message" => "ไม่พบข้อมูลสำหรับเดือน $targetMonth", "imported" => 0]);
        exit;
    }

    // === 4. นำข้อมูลไป Insert/Update ใน MySQL ===
    // ใช้ INSERT ... ON DUPLICATE KEY UPDATE เพื่อถ้าข้อมูลเดือนนี้มีอยู่แล้วให้แก้ไข (อัปเดต) แทนที่จะเพิ่มซ้ำ
    $insertQuery = "
        INSERT INTO invs_monthly_stock 
        (`year_month`, `working_code`, `drug_name`, `last_pack_ratio`, `fw_qty`, `fw_value`, `rcv_qty`, `rcv_value`, `dis_qty`, `dis_value`, `rm_qty`, `rm_value`, `diff_x_value`)
        VALUES 
        (:year_month, :working_code, :drug_name, :last_pack_ratio, :fw_qty, :fw_value, :rcv_qty, :rcv_value, :dis_qty, :dis_value, :rm_qty, :rm_value, :diff_x_value)
        ON DUPLICATE KEY UPDATE 
        `drug_name` = VALUES(`drug_name`),
        `fw_qty` = VALUES(`fw_qty`),
        `fw_value` = VALUES(`fw_value`),
        `rcv_qty` = VALUES(`rcv_qty`),
        `rcv_value` = VALUES(`rcv_value`),
        `dis_qty` = VALUES(`dis_qty`),
        `dis_value` = VALUES(`dis_value`),
        `rm_qty` = VALUES(`rm_qty`),
        `rm_value` = VALUES(`rm_value`),
        `diff_x_value` = VALUES(`diff_x_value`)
    ";
    
    $stmtMySql = $connMySql->prepare($insertQuery);
    
    // เริ่ม Transaction เพื่อความเร็วในการ Insert ข้อมูลจำนวนมาก
    $connMySql->beginTransaction();
    
    $count = 0;
    foreach ($data as $row) {
        $stmtMySql->execute([
            ':year_month'      => $targetMonth,
            ':working_code'    => $row['WORKING_CODE'],
            ':drug_name'       => $row['DRUG_NAME'],
            ':last_pack_ratio' => $row['LAST_PACK_RATIO'],
            ':fw_qty'          => $row['FW_QTY'] ?? 0,
            ':fw_value'        => $row['FW_VALUE'] ?? 0,
            ':rcv_qty'         => $row['RCV_QTY'] ?? 0,
            ':rcv_value'       => $row['RCV_VALUE'] ?? 0,
            ':dis_qty'         => $row['DIS_QTY'] ?? 0,
            ':dis_value'       => $row['DIS_VALUE'] ?? 0,
            ':rm_qty'          => $row['RM_QTY'] ?? 0,
            ':rm_value'        => $row['RM_VALUE'] ?? 0,
            ':diff_x_value'    => $row['X'] ?? 0
        ]);
        $count++;
    }
    
    $connMySql->commit();

    echo json_encode([
        "status" => "success", 
        "message" => "ซิงค์ข้อมูลเดือน $targetMonth สำเร็จ!", 
        "imported" => $count
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (isset($connMySql) && $connMySql->inTransaction()) {
        $connMySql->rollBack();
    }
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
