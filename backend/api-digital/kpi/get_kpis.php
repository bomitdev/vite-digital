<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . '/../../config.php';

try {
    $sql = "SELECT k.*, 
                   c.name as category_name,
                   l.name as kpi_level_name,
                   p.code as periodicity_code,
                   p.name as periodicity_name,
                   u.name as unit_name,
                   ct.code as calc_type_code,
                   ct.name as calc_type_name
            FROM kpi_definitions k 
            LEFT JOIN kpi_categories c ON k.category_id = c.id 
            LEFT JOIN kpi_levels l ON k.kpi_level_id = l.id
            LEFT JOIN kpi_periodicities p ON k.kpi_periodicity_id = p.id
            LEFT JOIN kpi_units u ON k.unit_id = u.id
            LEFT JOIN kpi_calculation_types ct ON k.calculation_type_id = ct.id
            ORDER BY c.id, k.id";
    $stmt = $pdo2->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Patch for frontend compatibility if needed, though we selected k.* so old values persist.
    // Let's overwrite them with the normalized source of truth if available, 
    // to ensure we are actually using the relational data.
    foreach ($rows as &$row) {
        if ($row['kpi_level_name']) $row['kpi_level'] = $row['kpi_level_name'];
        if ($row['periodicity_code']) $row['kpi_periodicity'] = $row['periodicity_code'];
        if ($row['unit_name']) $row['unit'] = $row['unit_name'];
        if ($row['calc_type_code']) $row['calculation_type'] = $row['calc_type_code'];
    }

    echo json_encode($rows);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
