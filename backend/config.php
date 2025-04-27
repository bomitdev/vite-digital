<?php

require_once 'vendor/autoload.php'; // โหลด autoload ของ Composer

// สร้างตัวแปร dotenv
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load(); // โหลดค่าจาก .env

// อ่านค่าจาก .env
$db1 = [
    'host' => $_ENV['DB1_HOST'],
    'name' => $_ENV['DB1_NAME'],
    'user' => $_ENV['DB1_USER'],
    'pass' => $_ENV['DB1_PASS']
];

$db2 = [
    'host' => $_ENV['DB2_HOST'],
    'name' => $_ENV['DB2_NAME'],
    'user' => $_ENV['DB2_USER'],
    'pass' => $_ENV['DB2_PASS']

];

function connectDatabase($config)
{
    try {
        $pdo = new PDO(
            "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4",
            $config['user'],
            $config['pass']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        return $pdo;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// สร้างการเชื่อมต่อ กับฐานข้อมูล 1 
$pdo1 = connectDatabase($db1);
$pdo1 = new PDO("mysql:host={$db1['host']};dbname={$db1['name']};charset=utf8mb4", $db1['user'], $db1['pass']);
$pdo1->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// กำหนด charset ให้เป็น utf8mb4
$pdo1->exec("SET NAMES utf8mb4");
$pdo1->exec("SET CHARACTER SET utf8mb4");
$pdo1->exec("SET character_set_results=utf8mb4");
//echo "Connected to database 1 successfully!";

// สร้างการเชื่อมต่อ กับฐานข้อมูล 2
$pdo2 = connectDatabase($db2);
$pdo2 = new PDO("mysql:host={$db2['host']};dbname={$db2['name']};charset=utf8mb4", $db2['user'], $db2['pass']);
$pdo2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// กำหนด charset ให้เป็น utf8mb4
$pdo2->exec("SET NAMES utf8mb4");
$pdo2->exec("SET CHARACTER SET utf8mb4");
$pdo2->exec("SET character_set_results=utf8mb4");
//echo "Connected to database 2 successfully!";
