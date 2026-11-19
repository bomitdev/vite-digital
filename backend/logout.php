<?php
session_start();



require_once 'config.php';
require_once 'audit_log_helper.php';

if (isset($pdo2)) {
    recordAuditLog($pdo2, [
        'module' => 'auth',
        'action' => 'LOGOUT',
        'status_code' => 200,
        'details' => 'ออกจากระบบ',
    ], $pdo3 ?? null);
}

session_destroy();

echo json_encode(["success" => true]);
exit;
