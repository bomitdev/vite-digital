<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $search = trim($_GET['search'] ?? '');
        $activeOnly = isset($_GET['active_only']) ? (int)$_GET['active_only'] : 0;

        $sql = "SELECT m.*, 
                       (SELECT COUNT(*) FROM inv_purchase_orders WHERE buy_method = m.code) as po_count 
                FROM inv_buy_methods m 
                WHERE 1=1";
        $params = [];

        if ($activeOnly) {
            $sql .= " AND m.active = 1";
        }

        if (!empty($search)) {
            $sql .= " AND (m.code LIKE ? OR m.name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY m.is_default DESC, CAST(m.code AS UNSIGNED) ASC, m.code ASC";

        $stmt = $pdo2->prepare($sql);
        $stmt->execute($params);
        $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'data' => $methods,
            'count' => count($methods)
        ], JSON_UNESCAPED_UNICODE);

    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true) ?: $_POST;
        $action = trim($data['action'] ?? '');

        // Action 1: Import from INVS SQL Server BUYMETHOD table
        if ($action === 'import_invs') {
            $sqlSrvHost = "192.168.2.19, 1433";
            $sqlSrvDb   = "invs";
            $sqlSrvUser = "sa";
            $sqlSrvPass = "Cnm@10985";

            try {
                $connSqlSrv = new PDO("sqlsrv:server=$sqlSrvHost;Database=$sqlSrvDb", $sqlSrvUser, $sqlSrvPass);
                $connSqlSrv->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $q = "SELECT RTRIM(BUYMET_CODE) as code, RTRIM(BUYMET_DES) as name, DEAL_DAYS, HIDE, DEFAULT_FLAG 
                      FROM BUYMETHOD 
                      WHERE BUYMET_DES IS NOT NULL AND LTRIM(RTRIM(BUYMET_DES)) <> '' AND LTRIM(RTRIM(BUYMET_DES)) <> '-'
                      ORDER BY CAST(BUYMET_CODE AS INT) ASC";
                $s = $connSqlSrv->query($q);
                $invsMethods = $s->fetchAll(PDO::FETCH_ASSOC);

                $existingCodes = $pdo2->query("SELECT code FROM inv_buy_methods")->fetchAll(PDO::FETCH_COLUMN);
                $existingMap = array_fill_keys(array_map('trim', $existingCodes), true);

                $insStmt = $pdo2->prepare("INSERT INTO inv_buy_methods (code, name, is_default, deal_days, active) VALUES (?, ?, ?, ?, ?)");
                $imported = 0;

                foreach ($invsMethods as $im) {
                    $c = trim($im['code'] ?? '');
                    $n = trim($im['name'] ?? '');
                    if (empty($c) || empty($n) || isset($existingMap[$c])) {
                        continue;
                    }
                    $isDef = (isset($im['DEFAULT_FLAG']) && strtoupper(trim($im['DEFAULT_FLAG'])) === 'Y') ? 1 : 0;
                    $dealDays = !empty($im['DEAL_DAYS']) ? (int)$im['DEAL_DAYS'] : 30;
                    $active = (isset($im['HIDE']) && strtoupper(trim($im['HIDE'])) === 'Y') ? 0 : 1;

                    $insStmt->execute([$c, $n, $isDef, $dealDays, $active]);
                    $existingMap[$c] = true;
                    $imported++;
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => "นำเข้าวิธีจัดซื้อจาก INVS สำเร็จ {$imported} รายการ (จากทั้งหมด " . count($invsMethods) . " รายการ)",
                    'imported' => $imported
                ], JSON_UNESCAPED_UNICODE);
                exit;

            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูล INVS: ' . $e->getMessage()]);
                exit;
            }
        }

        // Action 2: Seed default procurement methods
        if ($action === 'seed_defaults') {
            $defaults = [
                ['code' => '13', 'name' => 'วิธีเฉพาะเจาะจง', 'is_default' => 1, 'deal_days' => 60, 'active' => 1],
                ['code' => '14', 'name' => 'วิธีเฉพาะเจาะจง (สัญญา)', 'is_default' => 0, 'deal_days' => 30, 'active' => 1],
                ['code' => '8',  'name' => 'วิธีประกวดราคาอิเล็กทรอนิกส์ (e-Bidding)', 'is_default' => 0, 'deal_days' => 30, 'active' => 1],
                ['code' => '12', 'name' => 'วิธีคัดเลือก', 'is_default' => 0, 'deal_days' => 30, 'active' => 1],
                ['code' => '1',  'name' => 'ตกลงราคา', 'is_default' => 0, 'deal_days' => 30, 'active' => 1],
                ['code' => '99', 'name' => 'รับบริจาค / รับสนับสนุน', 'is_default' => 0, 'deal_days' => 30, 'active' => 1],
                ['code' => '41', 'name' => 'ยืม', 'is_default' => 0, 'deal_days' => 0, 'active' => 1],
                ['code' => '90', 'name' => 'รับโอน', 'is_default' => 0, 'deal_days' => 0, 'active' => 1]
            ];

            $existingCodes = $pdo2->query("SELECT code FROM inv_buy_methods")->fetchAll(PDO::FETCH_COLUMN);
            $existingMap = array_fill_keys(array_map('trim', $existingCodes), true);

            $insStmt = $pdo2->prepare("INSERT INTO inv_buy_methods (code, name, is_default, deal_days, active) VALUES (?, ?, ?, ?, ?)");
            $added = 0;

            foreach ($defaults as $d) {
                if (!isset($existingMap[$d['code']])) {
                    $insStmt->execute([$d['code'], $d['name'], $d['is_default'], $d['deal_days'], $d['active']]);
                    $existingMap[$d['code']] = true;
                    $added++;
                }
            }

            echo json_encode([
                'status' => 'success',
                'message' => "เพิ่มวิธีจัดซื้อมาตรฐานสำเร็จ {$added} รายการ",
                'added' => $added
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Standard Single Method Create
        $code = trim($data['code'] ?? '');
        $name = trim($data['name'] ?? '');
        $dealDays = isset($data['deal_days']) ? (int)$data['deal_days'] : 30;
        $isDefault = !empty($data['is_default']) ? 1 : 0;
        $active = isset($data['active']) ? (int)$data['active'] : 1;

        if (empty($code) || empty($name)) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุรหัสวิธีจัดซื้อและชื่อวิธีจัดซื้อ']);
            exit;
        }

        $chk = $pdo2->prepare("SELECT id FROM inv_buy_methods WHERE code = ?");
        $chk->execute([$code]);
        if ($chk->fetch()) {
            echo json_encode(['status' => 'error', 'message' => "รหัสวิธีจัดซื้อนี้ ('{$code}') มีอยู่ในระบบแล้ว"]);
            exit;
        }

        if ($isDefault) {
            $pdo2->exec("UPDATE inv_buy_methods SET is_default = 0");
        }

        $stmt = $pdo2->prepare("INSERT INTO inv_buy_methods (code, name, is_default, deal_days, active) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$code, $name, $isDefault, $dealDays, $active]);

        echo json_encode([
            'status' => 'success',
            'message' => 'เพิ่มวิธีจัดซื้อเรียบร้อยแล้ว',
            'id' => $pdo2->lastInsertId()
        ], JSON_UNESCAPED_UNICODE);

    } elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true) ?: [];
        $id = (int)($data['id'] ?? 0);
        $code = trim($data['code'] ?? '');
        $name = trim($data['name'] ?? '');
        $dealDays = isset($data['deal_days']) ? (int)$data['deal_days'] : 30;
        $isDefault = !empty($data['is_default']) ? 1 : 0;
        $active = isset($data['active']) ? (int)$data['active'] : 1;

        if (!$id || empty($code) || empty($name)) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุ ID, รหัส และชื่อวิธีจัดซื้อ']);
            exit;
        }

        $chk = $pdo2->prepare("SELECT id FROM inv_buy_methods WHERE code = ? AND id != ?");
        $chk->execute([$code, $id]);
        if ($chk->fetch()) {
            echo json_encode(['status' => 'error', 'message' => "รหัสวิธีจัดซื้อนี้ ('{$code}') ซ้ำกับรายการอื่น"]);
            exit;
        }

        if ($isDefault) {
            $pdo2->exec("UPDATE inv_buy_methods SET is_default = 0");
        }

        $stmt = $pdo2->prepare("UPDATE inv_buy_methods SET code = ?, name = ?, is_default = ?, deal_days = ?, active = ? WHERE id = ?");
        $stmt->execute([$code, $name, $isDefault, $dealDays, $active, $id]);

        echo json_encode([
            'status' => 'success',
            'message' => 'อัปเดตข้อมูลวิธีจัดซื้อเรียบร้อยแล้ว'
        ], JSON_UNESCAPED_UNICODE);

    } elseif ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'Missing ID']);
            exit;
        }

        $st = $pdo2->prepare("SELECT code, name FROM inv_buy_methods WHERE id = ?");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลวิธีจัดซื้อนี้']);
            exit;
        }

        $chk = $pdo2->prepare("SELECT COUNT(*) FROM inv_purchase_orders WHERE buy_method = ?");
        $chk->execute([$row['code']]);
        $count = (int)$chk->fetchColumn();

        if ($count > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => "ไม่สามารถลบได้ เนื่องจากมีใบสั่งซื้อ (PO) ใช้งานวิธีจัดซื้อนี้อยู่ ({$count} รายการ) แนะนำให้เปลี่ยนสถานะเป็น 'ปิดใช้งาน' แทน"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $pdo2->prepare("DELETE FROM inv_buy_methods WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode([
            'status' => 'success',
            'message' => 'ลบวิธีจัดซื้อเรียบร้อยแล้ว'
        ], JSON_UNESCAPED_UNICODE);

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
