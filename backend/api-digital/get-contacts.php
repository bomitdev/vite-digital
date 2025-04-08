<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require '../config.php';

$sql = "SELECT name, phone FROM employees";
$stmt = $pdo->query($sql);
$contacts = $stmt->fetchAll();

echo json_encode($contacts);
?>
