<?php
require_once '../../config.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id']) || empty($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'Server ID is required.']);
        exit;
    }

    if (!isset($data['server_name']) || empty($data['server_name'])) {
        echo json_encode(['success' => false, 'message' => 'Server Name is required.']);
        exit;
    }

    $sql = "UPDATE it_servers SET 
                server_name = :server_name,
                server_type = :server_type,
                ip_address = :ip_address,
                os = :os,
                version = :version,
                cpu = :cpu,
                ram = :ram,
                storage = :storage,
                role = :role,
                location = :location,
                user_name = :user_name,
                status = :status,
                notes = :notes
            WHERE id = :id";

    $stmt = $pdo2->prepare($sql);

    $success = $stmt->execute([
        ':server_name' => $data['server_name'],
        ':server_type' => $data['server_type'] ?? 'Physical',
        ':ip_address' => $data['ip_address'] ?? null,
        ':os' => $data['os'] ?? null,
        ':version' => $data['version'] ?? null,
        ':cpu' => $data['cpu'] ?? null,
        ':ram' => $data['ram'] ?? null,
        ':storage' => $data['storage'] ?? null,
        ':role' => $data['role'] ?? null,
        ':location' => $data['location'] ?? null,
        ':user_name' => $data['user_name'] ?? null,
        ':status' => $data['status'] ?? 'active',
        ':notes' => $data['notes'] ?? null,
        ':id' => $data['id']
    ]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'อัปเดตข้อมูล Server เรียบร้อยแล้ว']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update server.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
