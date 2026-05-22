<?php
require __DIR__ . '/../../config.php';

try {
    $posSql = "
        SELECT 
            CONCAT(pos.HR_POSITION_NAME, 
                IF(hl.HR_LEVEL_NAME IS NOT NULL AND hl.HR_LEVEL_NAME != '', CONCAT(' ', hl.HR_LEVEL_NAME), '')
            ) as position_name,
             pos.HR_POSITION_NAME as position_name2
        FROM hr_person p
        LEFT JOIN hr_position pos ON p.HR_POSITION_ID = pos.HR_POSITION_ID
        LEFT JOIN hr_level hl ON p.HR_LEVEL_ID = hl.HR_LEVEL_ID
        LIMIT 1
    ";
    $posStmt = $pdo3->prepare($posSql);
    $posStmt->execute();
    $posData = $posStmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $posData]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
