<?php
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';
require_once __DIR__ . '/../../moph_notify_helper.php';

$userData = authGuard();
$userId = (int)($userData['uid'] ?? 0);

try {
    $profile = getUserAccessProfile($pdo3, $userId);
    if (!$profile['is_admin']) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'เฉพาะผู้ดูแลระบบ (Administrator) เท่านั้นที่สามารถจัดการคีย์ MOPH Notify ได้',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to verify administrator permission'], JSON_UNESCAPED_UNICODE);
    exit;
}

ensureMophNotifyChannelsTable($pdo2);

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        echo json_encode([
            'status' => 'success',
            'default_notify_url' => DEFAULT_MOPH_NOTIFY_URL,
            'channels' => getMophNotifyChannels($pdo2, false, true),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
        exit;
    }

    $input = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($input)) {
        $input = [];
    }

    $action = trim((string)($input['action'] ?? ''));

    if ($action === 'save_channel') {
        $id = (int)($input['id'] ?? 0);
        $rawCode = strtolower(trim((string)($input['channel_code'] ?? '')));
        $channelName = trim((string)($input['channel_name'] ?? ''));
        $description = trim((string)($input['description'] ?? ''));
        $notifyUrl = trim((string)($input['notify_url'] ?? '')) ?: DEFAULT_MOPH_NOTIFY_URL;
        $clientKeyInput = trim((string)($input['client_key'] ?? ''));
        $secretKeyInput = trim((string)($input['secret_key'] ?? ''));
        $clearKeys = !empty($input['clear_keys']);
        $isActive = array_key_exists('is_active', $input) ? ((int)(bool)$input['is_active']) : 1;
        $sortOrder = isset($input['sort_order']) && is_numeric($input['sort_order']) ? (int)$input['sort_order'] : 100;

        if ($channelName === '') {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุชื่อกลุ่ม / ช่องทาง MOPH Notify'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!preg_match('#^https?://#i', $notifyUrl)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'URL ปลายทางต้องขึ้นต้นด้วย https:// หรือ http://'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($id > 0) {
            $stmtExisting = $pdo2->prepare("SELECT * FROM moph_notify_channels WHERE id = :id LIMIT 1");
            $stmtExisting->execute([':id' => $id]);
            $existing = $stmtExisting->fetch(PDO::FETCH_ASSOC);

            if (!$existing) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'ไม่พบช่องทางที่ต้องการแก้ไข'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $channelCode = (string)$existing['channel_code'];
            if (empty($existing['is_system']) && $rawCode !== '' && $rawCode !== $channelCode) {
                if (!preg_match('/^[a-z0-9_]{2,64}$/', $rawCode) || $rawCode === 'custom') {
                    http_response_code(400);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'รหัสช่องทาง (channel_code) ต้องเป็นตัวพิมพ์เล็ก a-z, ตัวเลข หรือ _ ความยาว 2-64 ตัวอักษร (และห้ามใช้คำว่า custom)',
                    ], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                $dup = $pdo2->prepare("SELECT id FROM moph_notify_channels WHERE channel_code = :code AND id <> :id LIMIT 1");
                $dup->execute([':code' => $rawCode, ':id' => $id]);
                if ($dup->fetch()) {
                    http_response_code(400);
                    echo json_encode(['status' => 'error', 'message' => "รหัสช่องทาง '{$rawCode}' ถูกใช้งานแล้ว"], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                $channelCode = $rawCode;
            }

            if ($clearKeys) {
                $finalCk = null;
                $finalSk = null;
            } else {
                $finalCk = $clientKeyInput !== '' ? $clientKeyInput : ($existing['client_key'] ?? null);
                $finalSk = $secretKeyInput !== '' ? $secretKeyInput : ($existing['secret_key'] ?? null);
            }

            $upd = $pdo2->prepare("
                UPDATE moph_notify_channels
                SET channel_code = :code,
                    channel_name = :name,
                    description = :desc,
                    notify_url = :url,
                    client_key = :ck,
                    secret_key = :sk,
                    is_active = :active,
                    sort_order = :sort_order,
                    updated_by = :uid
                WHERE id = :id
            ");
            $upd->execute([
                ':code' => $channelCode,
                ':name' => mb_substr($channelName, 0, 150, 'UTF-8'),
                ':desc' => $description !== '' ? mb_substr($description, 0, 255, 'UTF-8') : null,
                ':url' => mb_substr($notifyUrl, 0, 500, 'UTF-8'),
                ':ck' => $finalCk,
                ':sk' => $finalSk,
                ':active' => $isActive,
                ':sort_order' => $sortOrder,
                ':uid' => $userId,
                ':id' => $id,
            ]);

            $savedId = $id;
            $msg = "บันทึกการตั้งค่าช่องทาง \"{$channelName}\" เรียบร้อยแล้ว";
        } else {
            if (!preg_match('/^[a-z0-9_]{2,64}$/', $rawCode) || $rawCode === 'custom') {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'กรุณาระบุรหัสช่องทาง (ภาษาอังกฤษพิมพ์เล็ก a-z, ตัวเลข 0-9 หรือ _ เช่น opdcard_group) 2-64 ตัวอักษร',
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $dup = $pdo2->prepare("SELECT id FROM moph_notify_channels WHERE channel_code = :code LIMIT 1");
            $dup->execute([':code' => $rawCode]);
            if ($dup->fetch()) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => "รหัสช่องทาง '{$rawCode}' มีอยู่แล้วในระบบ"], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $ins = $pdo2->prepare("
                INSERT INTO moph_notify_channels
                    (channel_code, channel_name, description, notify_url, client_key, secret_key, is_active, is_system, sort_order, updated_by)
                VALUES
                    (:code, :name, :desc, :url, :ck, :sk, :active, 0, :sort_order, :uid)
            ");
            $ins->execute([
                ':code' => $rawCode,
                ':name' => mb_substr($channelName, 0, 150, 'UTF-8'),
                ':desc' => $description !== '' ? mb_substr($description, 0, 255, 'UTF-8') : null,
                ':url' => mb_substr($notifyUrl, 0, 500, 'UTF-8'),
                ':ck' => $clientKeyInput !== '' ? $clientKeyInput : null,
                ':sk' => $secretKeyInput !== '' ? $secretKeyInput : null,
                ':active' => $isActive,
                ':sort_order' => $sortOrder,
                ':uid' => $userId,
            ]);

            $savedId = (int)$pdo2->lastInsertId();
            $msg = "เพิ่มช่องทาง MOPH Notify \"{$channelName}\" เรียบร้อยแล้ว";
        }

        $channels = getMophNotifyChannels($pdo2, false, true);
        $savedRow = null;
        foreach ($channels as $ch) {
            if ($ch['id'] === $savedId) {
                $savedRow = $ch;
                break;
            }
        }

        echo json_encode([
            'status' => 'success',
            'message' => $msg,
            'channel' => $savedRow,
            'channels' => $channels,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'toggle_active') {
        $id = (int)($input['id'] ?? 0);
        $isActive = !empty($input['is_active']) ? 1 : 0;

        $stmt = $pdo2->prepare("UPDATE moph_notify_channels SET is_active = :active, updated_by = :uid WHERE id = :id");
        $stmt->execute([':active' => $isActive, ':uid' => $userId, ':id' => $id]);

        echo json_encode([
            'status' => 'success',
            'message' => $isActive ? 'เปิดใช้งานช่องทางแล้ว' : 'ปิดใช้งานช่องทางชั่วคราวแล้ว',
            'channels' => getMophNotifyChannels($pdo2, false, true),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'delete_channel') {
        $id = (int)($input['id'] ?? 0);
        $stmt = $pdo2->prepare("SELECT * FROM moph_notify_channels WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบช่องทางที่ต้องการลบ'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!empty($row['is_system'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบช่องทางหลักของระบบได้ (สามารถปิดใช้งานหรือแก้ไขคีย์แทนได้)'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $pdo2->prepare("DELETE FROM moph_notify_channels WHERE id = :id")->execute([':id' => $id]);

        // If schedule settings referenced the deleted channel, safely reset to 'it'
        $checkTbl = $pdo2->query("SHOW TABLES LIKE 'schedule_notify_settings'")->fetch();
        if ($checkTbl) {
            $pdo2->prepare("UPDATE schedule_notify_settings SET channel_mode = 'it' WHERE channel_mode = :code")
                ->execute([':code' => (string)$row['channel_code']]);
        }

        echo json_encode([
            'status' => 'success',
            'message' => "ลบช่องทาง \"{$row['channel_name']}\" เรียบร้อยแล้ว",
            'channels' => getMophNotifyChannels($pdo2, false, true),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'test_channel') {
        $id = (int)($input['id'] ?? 0);
        $channelCode = strtolower(trim((string)($input['channel_code'] ?? '')));
        $draftUrl = trim((string)($input['notify_url'] ?? ''));
        $draftCk = trim((string)($input['client_key'] ?? ''));
        $draftSk = trim((string)($input['secret_key'] ?? ''));
        $label = trim((string)($input['channel_name'] ?? ''));

        $existingCreds = null;
        if ($id > 0) {
            $stmt = $pdo2->prepare("SELECT channel_code, channel_name FROM moph_notify_channels WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $found = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($found) {
                $channelCode = (string)$found['channel_code'];
                if ($label === '') {
                    $label = (string)$found['channel_name'];
                }
            }
        }
        if ($channelCode !== '') {
            $existingCreds = resolveEffectiveMophChannel($pdo2, $channelCode, true);
            if ($existingCreds && $label === '') {
                $label = $existingCreds['channel_name'];
            }
        }

        $url = $draftUrl !== '' ? $draftUrl : ($existingCreds['url'] ?? DEFAULT_MOPH_NOTIFY_URL);
        $ck = $draftCk !== '' ? $draftCk : ($existingCreds['client_key'] ?? '');
        $sk = $draftSk !== '' ? $draftSk : ($existingCreds['secret_key'] ?? '');

        if ($ck === '' || $sk === '') {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกทั้ง Client Key และ Secret Key ก่อนทดสอบส่ง'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!preg_match('#^https?://#i', $url)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'URL ปลายทางไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $nowText = date('d/m/Y H:i:s');
        $displayLabel = $label !== '' ? $label : ($channelCode !== '' ? $channelCode : 'MOPH Notify');
        $payload = [
            'messages' => [
                [
                    'type' => 'flex',
                    'altText' => "ทดสอบการเชื่อมต่อ MOPH Notify ({$displayLabel})",
                    'contents' => [
                        'type' => 'bubble',
                        'size' => 'mega',
                        'header' => [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'backgroundColor' => '#198754',
                            'paddingAll' => '14px',
                            'contents' => [
                                [
                                    'type' => 'text',
                                    'text' => '🔔 ทดสอบการเชื่อมต่อ MOPH Notify',
                                    'color' => '#ffffff',
                                    'weight' => 'bold',
                                    'size' => 'md',
                                ],
                            ],
                        ],
                        'body' => [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'spacing' => 'sm',
                            'contents' => [
                                ['type' => 'text', 'text' => "ช่องทาง: {$displayLabel}", 'weight' => 'bold', 'size' => 'sm', 'wrap' => true],
                                ['type' => 'text', 'text' => "สถานะ: คีย์สามารถเชื่อมต่อและส่งข้อความเข้า LINE ได้สำเร็จ", 'size' => 'xs', 'color' => '#495057', 'wrap' => true],
                                ['type' => 'text', 'text' => "เวลาทดสอบ: {$nowText}", 'size' => 'xxs', 'color' => '#6c757d'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $res = sendMophNotifyRequest($url, $ck, $sk, $payload);
        echo json_encode([
            'status' => $res['ok'] ? 'success' : 'error',
            'message' => $res['ok']
                ? "ทดสอบส่งเข้า \"{$displayLabel}\" สำเร็จแล้ว (HTTP {$res['http_code']})"
                : "ทดสอบส่งไม่สำเร็จ (HTTP {$res['http_code']}): " . ($res['curl_error'] ?: $res['raw']),
            'http_code' => $res['http_code'],
            'moph_response' => $res['decoded'] ?? $res['raw'],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

