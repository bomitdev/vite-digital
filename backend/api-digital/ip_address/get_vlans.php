<?php
require_once '../../config.php';
header('Content-Type: application/json');

try {
    $sql = "SELECT * FROM it_vlans ORDER BY id ASC";
    $stmt = $pdo2->query($sql);
    $vlans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $vlans
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
