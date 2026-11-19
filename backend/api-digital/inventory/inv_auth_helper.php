<?php
/**
 * Inventory Auth & Permission Helpers
 */
require_once __DIR__ . '/../../auth_utils.php';

function getInvCurrentUser(?PDO $pdo3 = null): array {
    $tokenUser = function_exists('authOptional') ? authOptional() : null;
    $uid = (int)($tokenUser['uid'] ?? 0);
    if ($uid <= 0) {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $uid = (int)($_SESSION['user_id'] ?? $_SESSION['uid'] ?? 0);
    }
    $fullname = '';
    $perms = [];

    if ($uid > 0 && $pdo3) {
        try {
            $st = $pdo3->prepare("SELECT HR_FNAME, HR_LNAME, access_user FROM hr_person WHERE ID = ? LIMIT 1");
            $st->execute([$uid]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $fullname = trim(($row['HR_FNAME'] ?? '') . ' ' . ($row['HR_LNAME'] ?? ''));
                $perms = array_filter(array_map('trim', explode(':', (string)($row['access_user'] ?? ''))));
            }
        } catch (Exception $e) {
            // ignore
        }
    }

    return [
        'uid' => $uid,
        'fullname' => $fullname,
        'perms' => $perms,
    ];
}

function isInvWarehouseManager(PDO $pdo2, ?PDO $pdo3, int $warehouseId, array $currentUser): bool {
    $uid = (int)($currentUser['uid'] ?? 0);
    if ($uid <= 0) return false;

    $perms = $currentUser['perms'] ?? [];
    if (!empty(array_intersect($perms, ['administrator', 'menu_inventory_admin', 'menu_inventory_manage']))) {
        return true;
    }

    if ($warehouseId > 0) {
        $stWhUser = $pdo2->prepare("SELECT access_level FROM inv_user_warehouses WHERE warehouse_id = ? AND user_id = ? LIMIT 1");
        $stWhUser->execute([$warehouseId, $uid]);
        if ($stWhUser->fetchColumn()) {
            return true;
        }
    }

    return false;
}

function normalizeInvPersonName(string $name): string {
    $clean = trim(preg_replace('/\s+/u', ' ', $name));
    $clean = preg_replace('/^(ว่าที่\s*(?:ร้อยตรี|ร้อยโท|ร้อยเอก|ร\.ต\.|ร\.ท\.|ร\.อ\.)|นาย|นางสาว|นาง|น\.ส\.|ดร\.|พญ\.|นพ\.|ทพ\.|ทพญ\.|ภก\.|ภญ\.)\s*/u', '', $clean);
    return mb_strtolower(trim($clean), 'UTF-8');
}

function ensureInvRequisitionUserColumn(PDO $pdo2): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;
    try {
        $st = $pdo2->query("SHOW COLUMNS FROM inv_requisitions LIKE 'created_by_user_id'");
        if (!$st || !$st->fetch(PDO::FETCH_ASSOC)) {
            $pdo2->exec("ALTER TABLE inv_requisitions ADD COLUMN created_by_user_id INT NULL DEFAULT NULL AFTER requester_name");
        }
    } catch (Exception $e) {
        // ignore if table missing or insufficient privilege
    }
}

function isInvRequisitionOwner(array $reqRow, array $currentUser): bool {
    $uid = (int)($currentUser['uid'] ?? 0);
    $ownerUid = (int)($reqRow['created_by_user_id'] ?? 0);
    if ($uid > 0 && $ownerUid > 0) {
        return $uid === $ownerUid;
    }

    $userFullname = trim((string)($currentUser['fullname'] ?? ''));
    $reqName = trim((string)($reqRow['requester_name'] ?? ''));
    if ($userFullname === '' || $reqName === '') return false;

    $normUser = normalizeInvPersonName($userFullname);
    $normReq = normalizeInvPersonName($reqName);
    return $normUser !== '' && $normUser === $normReq;
}

