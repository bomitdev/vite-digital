<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

try {
    // Try to find the table in pdo2 (digital) first
    $sql = "DESCRIBE kpi_items";
    try {
        $stmt = $pdo2->prepare($sql);
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo2->query("SELECT * FROM kpi_items LIMIT 5");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'source' => 'pdo2 (digital)',
            'columns' => $columns,
            'sample_data' => $data
        ]);
        exit;
    } catch (Exception $e) {
        // failed in pdo2
    }

    // Try pdo3 (hosoffice)
    try {
        $stmt = $pdo3->prepare($sql);
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo3->query("SELECT * FROM kpi_items LIMIT 5");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'source' => 'pdo3 (hosoffice)',
            'columns' => $columns,
            'sample_data' => $data
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['error' => 'Table kpi_items not found in pdo2 or pdo3']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
