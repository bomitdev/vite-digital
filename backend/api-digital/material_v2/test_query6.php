<?php
require __DIR__ . '/../../config.php';
$posSql = "
    SELECT 
        CONCAT(pos.HR_POSITION_NAME, 
            IF(hl.HR_LEVEL_NAME IS NOT NULL AND hl.HR_LEVEL_NAME != '', CONCAT(' ', hl.HR_LEVEL_NAME), '')
        ) as position_name,
         pos.HR_POSITION_NAME as position_name2
    FROM hr_person p
    LEFT JOIN hr_prefix pfx ON p.HR_PREFIX_ID = pfx.HR_PREFIX_ID
    LEFT JOIN hr_position pos ON p.HR_POSITION_ID = pos.HR_POSITION_ID
    LEFT JOIN hr_level hl ON p.HR_LEVEL_ID = hl.HR_LEVEL_ID
    WHERE CONCAT(pfx.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) = :name
    LIMIT 1
";
$posStmt = $pdo3->prepare($posSql);
$posStmt->execute([':name' => 'นางสาวกนกวรรณ ศรีลาศักดิ์']);
print_r($posStmt->fetch(PDO::FETCH_ASSOC));
