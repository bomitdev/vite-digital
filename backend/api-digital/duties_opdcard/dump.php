<?php
require __DIR__ . '/../../config.php';
$stmt = $pdo2->query("SELECT * FROM employees_opdcard WHERE name LIKE '%ยุทธชัย%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
