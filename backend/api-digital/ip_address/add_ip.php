<?php
require_once '../../config.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['ip_address']) || empty($data['ip_address'])) {
        echo json_encode(['success' => false, 'message' => 'IP Address is required.']);
        exit;
    }

    // Check if IP already exists
    $stmtCheck = $pdo2->prepare("SELECT id FROM it_ip_addresses WHERE ip_address = ?");
    $stmtCheck->execute([$data['ip_address']]);
    if ($stmtCheck->fetch()) {
        echo json_encode(['success' => false, 'message' => 'IP Address นี้ถูกลงทะเบียนไว้แล้ว']);
        exit;
    }

    $sql = "INSERT INTO it_ip_addresses 
            (ip_address, device_name, mac_address, device_type, department, user_name, status, notes, vlan) 
            VALUES (:ip_address, :device_name, :mac_address, :device_type, :department, :user_name, :status, :notes, :vlan)";

    $stmt = $pdo2->prepare($sql);

    $success = $stmt->execute([
        ':ip_address' => $data['ip_address'],
        ':device_name' => $data['device_name'] ?? null,
        ':mac_address' => $data['mac_address'] ?? null,
        ':device_type' => $data['device_type'] ?? 'PC',
        ':department' => $data['department'] ?? null,
        ':user_name' => $data['user_name'] ?? null,
        ':status' => $data['status'] ?? 'active',
        ':notes' => $data['notes'] ?? null,
        ':vlan' => $data['vlan'] ?? 'Default'
    ]);

    if ($success) {
        // Trigger notification logic if required later
        // ...

        echo json_encode(['success' => true, 'message' => 'ลงทะเบียน IP สำเร็จ']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add IP address.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
