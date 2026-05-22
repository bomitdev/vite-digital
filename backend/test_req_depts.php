<?php
require 'config.php';
// Get requesters from hr_person in hosoffice
$sqlRequesters = "SELECT CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as name
                  FROM hr_person p
                  LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
                  WHERE p.HR_STATUS_ID = 1 AND p.HR_FNAME IS NOT NULL AND p.HR_FNAME != ''
                  ORDER BY p.HR_FNAME ASC";
$stmtRequesters = $pdo3->query($sqlRequesters);
$requesters = $stmtRequesters->fetchAll(PDO::FETCH_COLUMN);
print_r(array_slice($requesters, 0, 5));

$sqlDepts = "SELECT HR_DEPARTMENT_SUB_NAME as name
             FROM hr_department_sub
             WHERE HR_DEPARTMENT_SUB_NAME IS NOT NULL AND HR_DEPARTMENT_SUB_NAME != ''
             ORDER BY HR_DEPARTMENT_SUB_NAME ASC";
$stmtDepts = $pdo3->query($sqlDepts);
$departments = $stmtDepts->fetchAll(PDO::FETCH_COLUMN);
print_r(array_slice($departments, 0, 5));
