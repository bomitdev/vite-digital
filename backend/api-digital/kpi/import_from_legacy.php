<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

try {
    // 1. Fetch Legacy Data
    $sql = "SELECT * FROM kpi_items";
    // Try pdo2 first
    try {
        $stmt = $pdo2->query($sql);
        $legacyItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $stmt = $pdo3->query($sql);
        $legacyItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if (empty($legacyItems)) {
        echo json_encode(['status' => 'error', 'message' => 'No data found in kpi_items']);
        exit;
    }

    // 2. Prepare Insert
    $pdo2->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo2->exec("TRUNCATE TABLE kpi_entries");
    $pdo2->exec("TRUNCATE TABLE kpi_definitions");
    $pdo2->exec("SET FOREIGN_KEY_CHECKS = 1");

    $insertStmt = $pdo2->prepare("INSERT INTO kpi_definitions 
        (category_id, name, description, target_value, target_operator, unit, responsible_person, responsible_unit) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    $importedCount = 0;

    foreach ($legacyItems as $item) {
        // Map Fields
        $name = $item['kpi_name'];
        $description = $item['calculation_method'] ?? '';
        $responsible_person = $item['owner_name'] ?? '';
        $responsible_unit = $item['coordinator'] ?? '';

        // Map Category (KRA) - Simple heuristic or default
        // KRA11 -> 1, KRA12 -> 2, etc.
        $category_id = 1; // Default
        $kra = $item['kra'] ?? '';
        if (strpos($kra, 'KRA11') !== false) $category_id = 1;
        elseif (strpos($kra, 'KRA12') !== false) $category_id = 2;
        elseif (strpos($kra, 'Consumer') !== false || strpos($kra, 'Costumer') !== false) $category_id = 3;

        // Parse Target and Operator from Name
        $target_value = 0;
        $target_operator = '>=';
        $unit = '%'; // Default unit

        // Regex for things like ">= 80", "< 0.5", "ร้อยละ 80"
        if (preg_match('/(>=|<=|>|<|=)\s*(\d+(\.\d+)?)/', $name, $matches)) {
            $target_operator = $matches[1];
            $target_value = $matches[2];
        } elseif (preg_match('/ร้อยละ\s*(\d+(\.\d+)?)/', $name, $matches)) {
            $target_operator = '>='; // Assume minimal requirement for 'percentage' unless specified otherwise
            $target_value = $matches[1];
        }

        // Try to guess unit
        if (strpos($name, 'ร้อยละ') !== false || strpos($name, '%') !== false) {
            $unit = '%';
        } elseif (strpos($name, 'เวลารอคอย') !== false || strpos($name, 'นาที') !== false) {
            $unit = 'นาที';
        } elseif (strpos($name, 'วัน') !== false) {
            $unit = 'วัน';
        }

        $insertStmt->execute([
            $category_id,
            $name,
            $description,
            $target_value,
            $target_operator,
            $unit,
            $responsible_person,
            $responsible_unit
        ]);
        $importedCount++;
    }

    echo json_encode([
        'status' => 'success',
        'message' => "Imported $importedCount items from kpi_items",
        'sample' => $legacyItems[0]
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
