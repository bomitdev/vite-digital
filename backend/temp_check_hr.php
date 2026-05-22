<?php
require_once 'config.php';
$stmt = $pdo3->query("DESCRIBE hr_person");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
