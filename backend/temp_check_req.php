<?php
require_once 'config.php';
$stmt = $pdo2->query("DESCRIBE mt_requests");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
