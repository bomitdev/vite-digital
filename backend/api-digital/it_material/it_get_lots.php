<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$material_id = isset($_GET['material_id']) ? intval($_GET['material_id']) : 0;
$all = isset($_GET['all']) && ($_GET['all'] == '1' || $_GET['all'] === 'true');

if (!$all && $material_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid material ID']);
    exit;
}

try {
    if ($all) {
        $stmt = $pdo2->prepare("SELECT l.id, l.material_id, m.code AS material_code, m.name AS material_name, m.type AS material_type, m.unit AS material_unit, l.lot_number, l.vendor, l.price_per_unit, l.original_qty, l.remaining_qty, l.receive_date 
                                FROM mt_it_lots l 
                                LEFT JOIN mt_it_materials m ON l.material_id = m.id 
                                WHERE l.remaining_qty > 0 
                                ORDER BY m.code ASC, l.receive_date ASC, l.id ASC");
        $stmt->execute();
    } else {
        $stmt = $pdo2->prepare("SELECT id, lot_number, vendor, price_per_unit, original_qty, remaining_qty, receive_date FROM mt_it_lots WHERE material_id = :id AND remaining_qty > 0 ORDER BY receive_date ASC, id ASC");
        $stmt->execute([':id' => $material_id]);
    }
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
