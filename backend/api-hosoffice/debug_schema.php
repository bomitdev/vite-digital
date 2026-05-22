<?php
require '../config.php';

try {
    $stmt = $pdo3->query("DESCRIBE `10985_hos_fingerscan`");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($columns);
} catch (Exception $e) {
    echo $e->getMessage();
}
