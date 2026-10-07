<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$material_id = isset($_GET['material_id']) ? intval($_GET['material_id']) : 0;

if ($material_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid material ID']);
    exit;
}

try {
    $stmt = $pdo2->prepare("SELECT id, lot_number, price_per_unit, original_qty, remaining_qty, receive_date FROM pharmacy_lots WHERE material_id = :id AND remaining_qty > 0 ORDER BY receive_date ASC, id ASC");
    $stmt->execute([':id' => $material_id]);
    $lots = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $lots
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'DB Error: ' . $e->getMessage()
    ]);
}
