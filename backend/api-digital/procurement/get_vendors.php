<?php
header("Content-Type: application/json; charset=UTF-8");
require __DIR__ . '/../../config.php';

try {
    $stmt = $pdo2->query("SELECT source_id, name FROM asset_sources ORDER BY name ASC");
    $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'success', 'data' => $vendors]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
