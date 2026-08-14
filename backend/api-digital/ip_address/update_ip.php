<?php
require_once '../../config.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id']) || empty($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'IP Address ID is required.']);
        exit;
    }

    if (!isset($data['ip_address']) || empty($data['ip_address'])) {
        echo json_encode(['success' => false, 'message' => 'IP Address is required.']);
        exit;
    }

    // Check if IP already exists for another record
    $stmtCheck = $pdo2->prepare("SELECT id FROM it_ip_addresses WHERE ip_address = ? AND id != ?");
    $stmtCheck->execute([$data['ip_address'], $data['id']]);
    if ($stmtCheck->fetch()) {
        echo json_encode(['success' => false, 'message' => 'IP Address นี้ถูกลงทะเบียนไว้แล้ว']);
        exit;
    }

    $sql = "UPDATE it_ip_addresses SET 
                ip_address = :ip_address,
                device_name = :device_name,
                mac_address = :mac_address,
                device_type = :device_type,
                department = :department,
                user_name = :user_name,
                status = :status,
                notes = :notes,
                vlan = :vlan
            WHERE id = :id";

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
        ':vlan' => $data['vlan'] ?? 'Default',
        ':id' => $data['id']
    ]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'แก้ไขข้อมูลอัปเดตเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update IP address.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
