<?php
require 'config.php';
try {
    $stmt = $pdo3->query('SHOW CREATE TABLE kpi_items');
    if($stmt) print_r($stmt->fetch(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo $e->getMessage();
}
