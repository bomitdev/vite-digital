<?php
require '../config.php';

$name = "สุริยา";

try {
    $sql = "SELECT 
                p.ID,
                CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as FULLNAME,
                p.HR_DEPARTMENT_SUB_ID,
                p.HR_POSITION_ID,
                pos.HR_POSITION_NAME,
                hds.HR_DEPARTMENT_SUB_NAME
            FROM hr_person p
            LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
            LEFT JOIN hr_position pos ON p.HR_POSITION_ID = pos.HR_POSITION_ID
            LEFT JOIN hr_department_sub hds ON p.HR_DEPARTMENT_SUB_ID = hds.HR_DEPARTMENT_SUB_ID
            WHERE p.HR_FNAME LIKE :name";

    $stmt = $pdo3->prepare($sql);
    $stmt->execute([':name' => "%$name%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    print_r($results);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
