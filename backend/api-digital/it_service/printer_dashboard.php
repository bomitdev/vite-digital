<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// --- ป้องกันแจ้งเตือน Call to unknown function: 'snmpget' ใน VS Code ---
if (!function_exists('snmpget')) {
    /**
     * @param string $hostname
     * @param string $community
     * @param string $object_id
     * @param int $timeout
     * @param int $retries
     * @return mixed
     */
    function snmpget($hostname, $community, $object_id, $timeout = 1000000, $retries = 5) {
        return false;
    }
}
// -------------------------------------------------------------------

require_once __DIR__ . '/../../config.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'fetch_db') {
    $stmt = $pdo2->query("SELECT * FROM it_printers ORDER BY id ASC");
    $printers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $printers]);
    exit;
}

if ($action === 'fetch_live') {
    if (!extension_loaded('snmp')) {
        echo json_encode(['success' => false, 'message' => 'PHP SNMP extension is not loaded']);
        exit;
    }

    $results = [];
    $oid_page_count = '1.3.6.1.2.1.43.10.2.1.4.1.1'; // Standard Printer MIB

    // Fetch printers from DB
    $stmt = $pdo2->query("SELECT * FROM it_printers ORDER BY id ASC");
    $printers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($printers as $printer) {
        $ip = $printer['ip_address'];
        $community = $printer['community'] ?: 'public';
        $offset = (int)$printer['page_offset'];
        
        $snmp_result = @snmpget($ip, $community, $oid_page_count, 1000000, 1);
        
        if ($snmp_result === false) {
            $results[] = [
                'id' => $printer['id'],
                'name' => $printer['name'],
                'ip' => $ip,
                'status' => 'offline',
                'page_count' => 0,
                'last_update' => date('Y-m-d H:i:s')
            ];
        } else {
            $parts = explode(':', $snmp_result);
            $raw_count = isset($parts[1]) ? (int)trim($parts[1]) : 0;
            $page_count = $raw_count > 0 ? $raw_count + $offset : 0;
            
            $results[] = [
                'id' => $printer['id'],
                'name' => $printer['name'],
                'ip' => $ip,
                'status' => 'online',
                'page_count' => $page_count,
                'last_update' => date('Y-m-d H:i:s')
            ];
        }
    }
    echo json_encode(['success' => true, 'data' => $results]);
    exit;
}

if ($action === 'add') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!empty($data['name']) && !empty($data['ip'])) {
        $stmt = $pdo2->prepare("INSERT INTO it_printers (name, ip_address, community) VALUES (?, ?, 'public')");
        if ($stmt->execute([$data['name'], $data['ip']])) {
            echo json_encode(['success' => true, 'message' => 'เพิ่มเครื่องปริ้นสำเร็จ']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ไม่สามารถบันทึกข้อมูลได้']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    }
    exit;
}

if ($action === 'edit') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!empty($data['id']) && !empty($data['name']) && !empty($data['ip'])) {
        $stmt = $pdo2->prepare("UPDATE it_printers SET name = ?, ip_address = ? WHERE id = ?");
        if ($stmt->execute([$data['name'], $data['ip'], $data['id']])) {
            echo json_encode(['success' => true, 'message' => 'แก้ไขข้อมูลสำเร็จ']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ไม่สามารถแก้ไขข้อมูลได้']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    }
    exit;
}

if ($action === 'delete') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!empty($data['id'])) {
        $stmt = $pdo2->prepare("DELETE FROM it_printers WHERE id = ?");
        if ($stmt->execute([$data['id']])) {
            echo json_encode(['success' => true, 'message' => 'ลบเครื่องปริ้นสำเร็จ']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ไม่สามารถลบข้อมูลได้']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ไม่พบ ID เครื่องปริ้น']);
    }
    exit;
}

if ($action === 'reset_meter') {
    $data = json_decode(file_get_contents("php://input"), true);
    $printer_id = $data['id'] ?? null;
    if ($printer_id) {
        // 1. Get highest historical page count from logs
        $stmt_log = $pdo2->prepare("SELECT MAX(page_count) as max_count FROM it_printer_logs WHERE printer_id = ?");
        $stmt_log->execute([$printer_id]);
        $row = $stmt_log->fetch(PDO::FETCH_ASSOC);
        $highest_historical = $row ? (int)$row['max_count'] : 0;
        
        // 2. Fetch current raw count from printer
        $stmt_p = $pdo2->prepare("SELECT ip_address, community FROM it_printers WHERE id = ?");
        $stmt_p->execute([$printer_id]);
        $p = $stmt_p->fetch(PDO::FETCH_ASSOC);
        
        $oid_page_count = '1.3.6.1.2.1.43.10.2.1.4.1.1';
        $ip = $p['ip_address'];
        $community = $p['community'] ?: 'public';
        
        $snmp_result = @snmpget($ip, $community, $oid_page_count, 1000000, 1);
        if ($snmp_result !== false) {
            $parts = explode(':', $snmp_result);
            $raw_count = isset($parts[1]) ? (int)trim($parts[1]) : 0;
            
            if ($highest_historical >= $raw_count) {
                $new_offset = $highest_historical - $raw_count;
            } else {
                // Should not happen normally if it's a new printer starting at 0
                $new_offset = 0; 
            }
            
            $stmt_upd = $pdo2->prepare("UPDATE it_printers SET page_offset = ? WHERE id = ?");
            if ($stmt_upd->execute([$new_offset, $printer_id])) {
                echo json_encode(['success' => true, 'message' => 'เปลี่ยนเครื่องใหม่และชดเชยยอดสำเร็จ (Offset: '.$new_offset.')']);
            } else {
                echo json_encode(['success' => false, 'message' => 'ไม่สามารถอัปเดต Offset ได้']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'ติดต่อเครื่องปริ้นใหม่ไม่ได้ (กรุณาเสียบสาย LAN เครื่องใหม่ก่อน)']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบ']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
