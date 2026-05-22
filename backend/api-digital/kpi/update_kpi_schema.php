<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

try {
    // Check if columns exist
    $columns = ['responsible_person', 'responsible_unit'];
    $existingCols = [];

    $stmt = $pdo2->query("SHOW COLUMNS FROM kpi_definitions");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $existingCols[] = $row['Field'];
    }

    foreach ($columns as $col) {
        if (!in_array($col, $existingCols)) {
            $sql = "ALTER TABLE kpi_definitions ADD COLUMN $col VARCHAR(255) DEFAULT NULL";
            $pdo2->exec($sql);
            echo "Added column: $col <br>";
        } else {
            echo "Column exists: $col <br>";
        }
    }

    echo json_encode(['message' => 'Schema updated successfully']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
