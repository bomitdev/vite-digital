<?php
require 'config.php';
$stmt = $pdo2->query('SELECT DISTINCT kpi_level FROM kpi_definitions');
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));

