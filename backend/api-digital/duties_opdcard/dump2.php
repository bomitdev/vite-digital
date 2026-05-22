<?php
require __DIR__ . '/../../config.php';
$stmt = $pdo2->query("SELECT * FROM duties_opdcard WHERE employees_opdcard_id = 5 AND MONTH(date) = 4 AND YEAR(date) = 2026 ORDER BY date");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
