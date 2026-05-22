<?php
// backend/check_pass.php
require_once "config.php";
header('Content-Type: application/json; charset=utf-8');

try {
    $stmt = $pdo3->query("SELECT HR_USERNAME, HR_PASSWORD, HR_PASSWORD_HASH FROM hr_person WHERE HR_USERNAME IN ('suriya', 'top', 'angieyui111')");
    $users = $stmt->fetchAll();

    foreach ($users as &$user) {
        $pass = $user['HR_PASSWORD'];
        $user['is_123_md5'] = (strtoupper(md5('123')) === strtoupper($pass));
        $user['is_bcrypt'] = (strpos($pass, '$2y$') === 0);
    }

    echo json_encode($users, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
