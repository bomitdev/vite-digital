<?php
require __DIR__ . '/../../config.php';
$stmt = $pdo3->query("SHOW TABLES LIKE 'hr_prefix'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $pdo3->query("DESCRIBE hr_prefix");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
