<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . '/../../config.php';

try {
    $an = isset($_GET['an']) ? $_GET['an'] : '670002329';

    // เตรียม SQL Query
    $stmt = $pdo1->prepare("SELECT
	i.an,
	p.pname,
	p.fname,
	p.lname,
	a.pdx,
	a.dx0,
	a.dx1,
	a.dx2,
	a.dx3,
	a.dx4,
	a.dx5,
	GROUP_CONCAT( DISTINCT ioc.name )as icdname, io.opdate, io.optime, io.enddate, io.endtime,
	i.dchtype,
CASE
		WHEN i.dchtype = '04' THEN
		CONCAT( dt.NAME, '  ส่งต่อ  ', h.NAME ) ELSE dt.NAME 
	END AS discharge,
	CONCAT( d.NAME, '  เลชใบประกอบ  ', d.licenseno ) AS doctor_name
FROM
	ipt i
	LEFT JOIN an_stat a ON i.an = a.an
	LEFT JOIN dchtype dt ON i.dchtype = dt.dchtype
	LEFT JOIN ipt_doctor_list il ON il.an = i.an AND il.ipt_doctor_type_id = '1' AND il.active_doctor = 'Y'
	LEFT OUTER JOIN iptoprt io ON i.an = io.an
	LEFT OUTER JOIN ipt_oper_code ioc ON io.icd9 = ioc.icd9cm
	LEFT JOIN officer oi ON oi.officer_doctor_code = il.doctor
	LEFT JOIN officer_signature os ON os.officer_id = oi.officer_id
	LEFT JOIN doctor d ON il.doctor = d.`code`
	LEFT JOIN referout r ON i.an = r.vn
	LEFT OUTER JOIN hospcode h ON h.hospcode = r.refer_hospcode
	LEFT JOIN patient p ON i.hn = p.hn 
WHERE
	i.an = :an
    ");
    $stmt->bindParam(':an', $an);

    // Execute query
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    // ✅ ส่งข้อมูลกลับในรูปแบบ JSON
    echo json_encode($data);
} catch (PDOException $e) {
    echo json_encode(array("error" => "Connection failed: " . $e->getMessage()));
    exit();
}
