<?php
require_once 'config.php';
$stmt = $pdo3->query("
    SELECT DISTINCT CONCAT(IFNULL(pos.HR_POSITION_NAME,''), '', IFNULL(hl.HR_LEVEL_NAME,'')) as name
    FROM hr_person p
    LEFT JOIN hr_position pos ON p.HR_POSITION_ID = pos.HR_POSITION_ID
    LEFT JOIN hr_level hl ON p.HR_LEVEL_ID = hl.HR_LEVEL_ID
    WHERE p.HR_STATUS_ID = 1 AND pos.HR_POSITION_NAME IS NOT NULL
    LIMIT 20
");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
