<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once '../../config.php';

if (!isset($pdo2)) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // 1. Get global settings
    $stmtGlobal = $pdo2->query("SELECT key_name, setting_value FROM mt_global_settings");
    $globalSettingsRaw = $stmtGlobal->fetchAll(PDO::FETCH_ASSOC);
    $globalSettings = [];
    foreach ($globalSettingsRaw as $row) {
        $globalSettings[$row['key_name']] = $row['setting_value'];
    }

    // 2. Get department signers
    $stmtDepts = $pdo2->query("SELECT * FROM mt_department_signers ORDER BY department_name ASC");
    $departmentSigners = $stmtDepts->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'global' => $globalSettings,
        'departments' => $departmentSigners
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
