<?php

// โหลด autoload และ dotenv
require_once __DIR__ . '/vendor/autoload.php';

// Load CORS settings centrally
require_once __DIR__ . '/cors.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

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

$db3 = [
    'host' => $_ENV['DB3_HOST'],
    'name' => $_ENV['DB3_NAME'],
    'user' => $_ENV['DB3_USER'],
    'pass' => $_ENV['DB3_PASS']
];

/**
 * Creates a PDO connection based on provided configuration.
 *
 * @param array $config Database configuration containing host, name, user, and pass.
 * @return PDO
 */
function connectDatabase(array $config): PDO
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
        $pdo->exec("SET NAMES utf8mb4");
        $pdo->exec("SET CHARACTER SET utf8mb4");
        $pdo->exec("SET character_set_results=utf8mb4");
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'error',
            'message' => "Connection to {$config['host']} ({$config['name']}) failed: " . $e->getMessage()
        ]);
        exit;
    }
}

// สร้างการเชื่อมต่อ
/** @var PDO $pdo1 */
$pdo1 = connectDatabase($db1);
/** @var PDO $pdo2 */
$pdo2 = connectDatabase($db2);
/** @var PDO $pdo3 */
$pdo3 = connectDatabase($db3);

// try {
//     $pdo1->query("SELECT 1");
//     echo "DB1 connected OK<br>";
// } catch (Exception $e) {
//     echo "DB1 error: " . $e->getMessage() . "<br>";
// }

// try {
//     $pdo2->query("SELECT 1");
//     echo "DB2 connected OK<br>";
// } catch (Exception $e) {
//     echo "DB2 error: " . $e->getMessage() . "<br>";
// }
// try {
//     $pdo3->query("SELECT 1");
//     echo "DB3 connected OK<br>";
// } catch (Exception $e) {
//     echo "DB3 error: " . $e->getMessage() . "<br>";
// }
