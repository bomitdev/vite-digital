<?php
require __DIR__ . '/../../config.php';

try {
    $pdo2->beginTransaction();

    // 1. Fetch all levels to create a mapping from name -> ID
    $stmtLevels = $pdo2->query("SELECT id, name FROM kpi_levels");
    $levelsMap = [];
    while ($row = $stmtLevels->fetch(PDO::FETCH_ASSOC)) {
        $levelsMap[trim(mb_strtolower($row['name']))] = $row['id'];
    }

    echo "Found " . count($levelsMap) . " levels.\n";
    // Also, map parts (e.g. if name is KPI THIP, KPI-Thip, etc) - though we'll match exactly where possible.

    // 2. Fetch all KPIs that need migration
    $stmtKpis = $pdo2->query("SELECT id, kpi_level FROM kpi_definitions");
    $kpis = $stmtKpis->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($kpis) . " KPIs to process.\n";
    
    $updateStmt = $pdo2->prepare("UPDATE kpi_definitions SET kpi_level = :new_levels WHERE id = :id");
    
    $updatedCount = 0;
    foreach ($kpis as $kpi) {
        $id = $kpi['id'];
        $old_level_str = $kpi['kpi_level'];
        
        if (empty($old_level_str)) continue;
        
        // If it's already digits and commas, it's already migrated (e.g., '4', '4,6')
        if (preg_match('/^[0-9, ]+$/', $old_level_str)) {
            continue;
        }

        $parts = explode(',', $old_level_str);
        $new_ids = [];
        foreach ($parts as $part) {
            $cleaned = trim($part);
            if (empty($cleaned)) continue;
            
            $key = mb_strtolower($cleaned);
            if (isset($levelsMap[$key])) {
                $new_ids[] = $levelsMap[$key];
            } else {
                // If not found, log it or attempt to handle? Let's just keep looking.
                echo "Warning: KPI ID $id has unknown level '$cleaned'\n";
            }
        }
        
        if (!empty($new_ids)) {
            // Remove duplicates just in case
            $new_ids = array_unique($new_ids);
            $new_level_str = implode(',', $new_ids);
            
            $updateStmt->execute([
                ':new_levels' => $new_level_str,
                ':id' => $id
            ]);
            $updatedCount++;
        }
    }
    
    // 3. We also need to change the data type of the column if it's not long enough, 
    // but VARCHAR(50) should be enough for "4, 6". Still, let's make it VARCHAR(255) for safety.
    $pdo2->exec("ALTER TABLE kpi_definitions MODIFY kpi_level VARCHAR(255) DEFAULT NULL");
    echo "Modified column kpi_level to VARCHAR(255).\n";

    $pdo2->commit();
    echo "Migration completed successfully! Updated $updatedCount rows.\n";

} catch (Exception $e) {
    if (isset($pdo2) && $pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    echo "Migration failed: " . $e->getMessage() . "\n";
}
