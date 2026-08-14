<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['vlan']) || empty($data['vlan'])) {
        echo json_encode(['success' => false, 'message' => 'VLAN is required.']);
        exit;
    }

    if (!isset($data['start_ip']) || empty($data['start_ip']) || !filter_var($data['start_ip'], FILTER_VALIDATE_IP)) {
        echo json_encode(['success' => false, 'message' => 'รูปแบบ Start IP ไม่ถูกต้อง']);
        exit;
    }

    if (!isset($data['end_ip']) || empty($data['end_ip']) || !filter_var($data['end_ip'], FILTER_VALIDATE_IP)) {
        echo json_encode(['success' => false, 'message' => 'รูปแบบ End IP ไม่ถูกต้อง']);
        exit;
    }

    $vlan = $data['vlan'];
    $start_ip = $data['start_ip'];
    $end_ip = $data['end_ip'];

    $start_long = ip2long($start_ip);
    $end_long = ip2long($end_ip);

    if ($start_long === false || $end_long === false) {
        echo json_encode(['success' => false, 'message' => 'IP Address ไม่ถูกต้อง']);
        exit;
    }

    if ($start_long > $end_long) {
        echo json_encode(['success' => false, 'message' => 'Start IP ต้องน้อยกว่าหรือเท่ากับ End IP']);
        exit;
    }

    // Limit to 1024 IPs at a time to prevent infinite loops / overloading
    if (($end_long - $start_long) > 1024) {
        echo json_encode(['success' => false, 'message' => 'สามารถสร้างได้สูงสุดครั้งละ 1,024 IP เพื่อป้องกันระบบทำงานหนักเกินไป']);
        exit;
    }

    $created_count = 0;
    $skipped_count = 0;

    $stmtCheck = $pdo2->prepare("SELECT id FROM it_ip_addresses WHERE ip_address = ?");
    $stmtInsert = $pdo2->prepare("
        INSERT INTO it_ip_addresses (ip_address, vlan, status, device_type) 
        VALUES (?, ?, 'inactive', 'PC')
    ");

    $pdo2->beginTransaction();

    for ($i = $start_long; $i <= $end_long; $i++) {
        $ip = long2ip($i);
        
        // Skip network and broadcast addresses (basic assumption for /24, ends with .0 or .255)
        // This is a naive check but good for usability. If it ends with .0 or .255, we can optionally skip it.
        $last_octet = substr(strrchr($ip, "."), 1);
        if ($last_octet == '0' || $last_octet == '255') {
            // Uncomment to skip network/broadcast IPs automatically
            // continue; 
        }

        $stmtCheck->execute([$ip]);
        if (!$stmtCheck->fetch()) {
            $stmtInsert->execute([$ip, $vlan]);
            $created_count++;
        } else {
            $skipped_count++;
        }
    }

    $pdo2->commit();

    echo json_encode([
        'success' => true, 
        'message' => "สร้างสำเร็จ $created_count รายการ (ข้าม IP ที่ซ้ำ $skipped_count รายการ)",
        'created' => $created_count,
        'skipped' => $skipped_count
    ]);

} catch (PDOException $e) {
    if (isset($pdo2) && $pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
