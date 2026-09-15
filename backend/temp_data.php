<?php
require 'config.php';
$stmt = $pdo2->query('SELECT id, code FROM kpi_definitions LIMIT 10');
if($stmt) print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
