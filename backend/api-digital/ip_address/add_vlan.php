<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['vlan_name']) || empty(trim($data['vlan_name']))) {
        echo json_encode(['success' => false, 'message' => 'VLAN Name is required.']);
        exit;
    }

    $vlan_name = trim($data['vlan_name']);
    $description = $data['description'] ?? null;

    $stmtCheck = $pdo2->prepare("SELECT id FROM it_vlans WHERE vlan_name = ?");
    $stmtCheck->execute([$vlan_name]);
    if ($stmtCheck->fetch()) {
        echo json_encode(['success' => false, 'message' => 'ชื่อ VLAN นี้มีอยู่ในระบบแล้ว']);
        exit;
    }

    $sql = "INSERT INTO it_vlans (vlan_name, description) VALUES (:name, :desc)";
    $stmt = $pdo2->prepare($sql);
    $success = $stmt->execute([
        ':name' => $vlan_name,
        ':desc' => $description
    ]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'เพิ่ม VLAN สำเร็จ']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add VLAN.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
