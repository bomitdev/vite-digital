<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';
require_once __DIR__ . '/daily_repair_notify_service.php';
require_once __DIR__ . '/repair_auth_helper.php';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $userCtx = getGeneralRepairUserContext($pdo2, $pdo3);
        if (!$userCtx['can_manage']) {
            http_response_code(403);
            echo json_encode([
                'status' => 'error',
                'message' => 'ไม่มีสิทธิ์เข้าถึง: ตั้งค่าการแจ้งเตือนได้เฉพาะช่างและ Admin เท่านั้น'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $input = [
            'notify_time' => $_POST['notify_time'] ?? null,
            'is_enabled' => isset($_POST['is_enabled']) ? (int)$_POST['is_enabled'] : null,
            'channel_code' => $_POST['channel_code'] ?? null,
            'app_base_url' => $_POST['app_base_url'] ?? null,
        ];
        $updated = saveRepairNotifySettings($pdo2, $input);
        echo json_encode([
            'status' => 'success',
            'message' => 'บันทึกการตั้งค่าเวลาแจ้งเตือนเรียบร้อยแล้ว',
            'data' => $updated
        ], JSON_UNESCAPED_UNICODE);
    } else {
        $settings = getRepairNotifySettings($pdo2);
        echo json_encode([
            'status' => 'success',
            'data' => $settings
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

