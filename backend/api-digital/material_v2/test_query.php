<?php
require '../../config.php';
$stmt = $pdo1->prepare("SELECT CONCAT(pos.HR_POSITION_NAME, IF(hl.HR_LEVEL_NAME IS NOT NULL AND hl.HR_LEVEL_NAME != '', CONCAT(' ', hl.HR_LEVEL_NAME), '')) as position_name, pos.HR_POSITION_NAME as position_name2 FROM hr_person p LEFT JOIN hr_position pos ON p.HR_POSITION_ID = pos.HR_POSITION_ID LEFT JOIN hr_level hl ON p.HR_LEVEL_ID = hl.HR_LEVEL_ID LIMIT 5");
$stmt->execute();
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
