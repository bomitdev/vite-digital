<?php
require_once '../../config.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['server_name']) || empty($data['server_name'])) {
        echo json_encode(['success' => false, 'message' => 'Server Name is required.']);
        exit;
    }

    $sql = "INSERT INTO it_servers 
            (server_name, server_type, ip_address, os, version, cpu, ram, storage, role, location, user_name, status, notes) 
            VALUES (:server_name, :server_type, :ip_address, :os, :version, :cpu, :ram, :storage, :role, :location, :user_name, :status, :notes)";

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
        ':notes' => $data['notes'] ?? null
    ]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'ลงทะเบียน Server สำเร็จ']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add server.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
