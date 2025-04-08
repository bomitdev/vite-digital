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
            "mysql:host={$config['host']};dbname={$config['name']};charset=utf8",
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

// สร้างการเชื่อมต่อ
$pdo1 = connectDatabase($db1);
$pdo2 = connectDatabase($db2);
