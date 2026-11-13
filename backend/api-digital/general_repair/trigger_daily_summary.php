<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';
require_once __DIR__ . '/daily_repair_notify_service.php';
require_once __DIR__ . '/repair_auth_helper.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid Request Method");
    }

    $userCtx = getGeneralRepairUserContext($pdo2, $pdo3);
    if (!$userCtx['can_manage']) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'ไม่มีสิทธิ์เข้าถึง: สรุปแจ้งเตือน MOPH เข้าถึงได้เฉพาะช่างและ Admin เท่านั้น'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $targetDate = isset($_POST['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['date'])
        ? $_POST['date']
        : date('Y-m-d');

    $isDryRun = isset($_POST['dry_run']) && in_array(strtolower((string)$_POST['dry_run']), ['1', 'true'], true);

    $result = dispatchDailyRepairNotify($pdo2, [
        'target_date' => $targetDate,
        'dry_run' => $isDryRun,
        'force' => true
    ]);

    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

