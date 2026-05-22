<?php
// backend/debug_test.php
require_once "config.php";

header('Content-Type: application/json; charset=utf-8');

$results = [
    'php_version' => PHP_VERSION,
    'db_connections' => [],
    'user_check' => null,
];

try {
    $results['db_connections']['db3'] = 'Attempting...';
    $stmt = $pdo3->query("SELECT 1");
    $results['db_connections']['db3'] = 'Connected';
} catch (Exception $e) {
    $results['db_connections']['db3'] = 'Failed: ' . $e->getMessage();
}

try {
    $stmt = $pdo3->query("SELECT COUNT(*) as count FROM hr_person");
    $count = $stmt->fetchColumn();
    $results['user_check'] = "Total users in hr_person: " . $count;

    $stmt = $pdo3->query("SELECT HR_USERNAME, HR_STATUS_ID FROM hr_person LIMIT 5");
    $users = $stmt->fetchAll();
    $results['sample_users'] = $users;
} catch (Exception $e) {
    $results['user_check'] = 'Error: ' . $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
