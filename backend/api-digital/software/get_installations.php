<?php
require_once '../../config.php';

header('Content-Type: application/json');

$software_id = isset($_GET['software_id']) ? $_GET['software_id'] : null;

if (!$software_id) {
    echo json_encode(['success' => false, 'message' => 'Missing parameter: software_id']);
    exit;
}

try {
    $sql = "SELECT * FROM sw_installations WHERE software_id = :software_id ORDER BY id DESC";
    $stmt = $pdo2->prepare($sql);
    $stmt->execute([':software_id' => $software_id]);
    $installations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $installations
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
