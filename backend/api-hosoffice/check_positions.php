<?php
require '../config.php';

try {
    $sql = "SELECT HR_POSITION_ID,HR_POSITION_NAME FROM hr_position WHERE HR_POSITION_ID = '24'  OR HR_POSITION_ID IN ('66','69','71')";
    $stmt = $pdo3->prepare($sql);
    $stmt->execute();
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    print_r($positions);

    // Also check HR_PERSON_TYPE just in case
    echo "\n -- Types -- \n";
    $sql_type = "SELECT HR_PERSON_TYPE_ID,HR_PERSON_TYPE_NAME FROM hr_person_type WHERE HR_PERSON_TYPE_ID IN ('06')";
    $stmt_type = $pdo3->prepare($sql_type);
    $stmt_type->execute();
    $types = $stmt_type->fetchAll(PDO::FETCH_ASSOC);
    print_r($types);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
