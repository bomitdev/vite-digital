<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'VLAN ID is required.']);
        exit;
    }

    // Check if any IPs are using this VLAN (assuming IP table uses vlan_name instead of ID to be safe based on our earlier change)
    // We should fetch the vlan_name first.
    $stmtVlan = $pdo2->prepare("SELECT vlan_name FROM it_vlans WHERE id = ?");
    $stmtVlan->execute([$data['id']]);
    $vlan = $stmtVlan->fetch(PDO::FETCH_ASSOC);

    if (!$vlan) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูล VLAN นี้']);
        exit;
    }

    $stmtCheck = $pdo2->prepare("SELECT COUNT(*) FROM it_ip_addresses WHERE vlan = ?");
    $stmtCheck->execute([$vlan['vlan_name']]);
    $count = $stmtCheck->fetchColumn();

    if ($count > 0) {
        echo json_encode(['success' => false, 'message' => "ไม่สามารถลบได้ เนื่องจากมี IP ผูกอยู่กับ VLAN นี้จำนวน {$count} รายการ"]);
        exit;
    }

    $stmt = $pdo2->prepare("DELETE FROM it_vlans WHERE id = ?");
    $success = $stmt->execute([$data['id']]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'ลบ VLAN สำเร็จ']);
    } else {
        echo json_encode(['success' => false, 'message' => 'ลบข้อมูลไม่สำเร็จ']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
