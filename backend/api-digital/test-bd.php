<?php
require '../config.php';

try {
    $pdo1 = new PDO("mysql:host={$db2['host']};dbname={$db2['name']};charset=utf8", $db2['user'], $db2['pass']);
    $pdo1->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connection successful!";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
