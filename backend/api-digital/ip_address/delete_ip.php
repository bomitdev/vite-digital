<?php
require_once '../../config.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id']) || empty($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID is required.']);
        exit;
    }

    $sql = "DELETE FROM it_ip_addresses WHERE id = :id";
    $stmt = $pdo2->prepare($sql);
    $success = $stmt->execute([':id' => $data['id']]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'ลบข้อมูลสำเร็จ']);
    } else {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถลบข้อมูลได้']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
