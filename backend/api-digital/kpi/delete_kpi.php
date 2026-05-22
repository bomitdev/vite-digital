<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . '/../../config.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['id'])) {
        throw new Exception("Missing KPI ID");
    }

    // Optional: Check constraint if entries exist, maybe CASCADE or Block. 
    // For now, let's just delete the definition.
    // If foreign key constraint is active without cascade, this might fail if entries exist.
    // Let's try to delete entries first to be safe (Simulate CASCADE)

    $pdo2->beginTransaction();

    $stmt1 = $pdo2->prepare("DELETE FROM kpi_entries WHERE kpi_id = :id");
    $stmt1->execute([':id' => $data['id']]);

    $stmt2 = $pdo2->prepare("DELETE FROM kpi_definitions WHERE id = :id");
    $stmt2->execute([':id' => $data['id']]);

    $pdo2->commit();

    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
