<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

try {
    // Delete KPIs with empty name or category_id (orphaned or bad data)
    // Only delete if they have no valid name.
    // Also delete if category_id is 0 or NULL which shouldn't happen in new system.

    $sql = "DELETE FROM kpi_definitions WHERE name IS NULL OR name = '' OR category_id IS NULL OR category_id = 0";
    $stmt = $pdo2->prepare($sql);
    $stmt->execute();

    $count = $stmt->rowCount();

    echo json_encode(['status' => 'success', 'deleted_count' => $count]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
