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

        $sql = "SELECT u.*, 
                       (SELECT COUNT(*) FROM inv_items WHERE unit_id = u.id OR purchase_unit_id = u.id OR issue_unit_id = u.id) as item_count 
                FROM inv_units u 
                WHERE 1=1";
        $params = [];

        if ($activeOnly) {
            $sql .= " AND u.active = 1";
        }

        if (!empty($search)) {
            $sql .= " AND (u.name LIKE ? OR u.code LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY u.name ASC";

        $stmt = $pdo2->prepare($sql);
        $stmt->execute($params);
        $units = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'data' => $units,
            'count' => count($units)
        ], JSON_UNESCAPED_UNICODE);

    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true) ?: $_POST;
        $action = trim($data['action'] ?? '');

        // Action 1: Import units from INVS SQL Server (SALE_UNIT)
        if ($action === 'import_invs') {
            $sqlSrvHost = "192.168.2.19, 1433";
            $sqlSrvDb   = "invs";
            $sqlSrvUser = "sa";
            $sqlSrvPass = "Cnm@10985";

            try {
                $connSqlSrv = new PDO("sqlsrv:server=$sqlSrvHost;Database=$sqlSrvDb", $sqlSrvUser, $sqlSrvPass);
                $connSqlSrv->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $q = "SELECT SU_ID, RTRIM(SALE_UNIT) as name, HIDE FROM SALE_UNIT WHERE SALE_UNIT IS NOT NULL AND LTRIM(RTRIM(SALE_UNIT)) <> '' ORDER BY SU_ID ASC";
                $s = $connSqlSrv->query($q);
                $invsUnits = $s->fetchAll(PDO::FETCH_ASSOC);

                // Fetch existing units from MySQL
                $existing = $pdo2->query("SELECT name FROM inv_units")->fetchAll(PDO::FETCH_COLUMN);
                $existingMap = array_fill_keys(array_map('trim', $existing), true);

                $insStmt = $pdo2->prepare("INSERT INTO inv_units (code, name, active) VALUES (?, ?, ?)");
                $imported = 0;

                foreach ($invsUnits as $u) {
                    $uName = trim($u['name'] ?? '');
                    if (empty($uName) || isset($existingMap[$uName])) {
                        continue;
                    }
                    $code = !empty($u['SU_ID']) ? 'U' . str_pad($u['SU_ID'], 3, '0', STR_PAD_LEFT) : null;
                    $active = (isset($u['HIDE']) && strtoupper(trim($u['HIDE'])) === 'Y') ? 0 : 1;
                    $insStmt->execute([$code, $uName, $active]);
                    $existingMap[$uName] = true;
                    $imported++;
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => "นำเข้าหน่วยนับจาก INVS สำเร็จ {$imported} รายการ (จากทั้งหมด " . count($invsUnits) . " รายการ)",
                    'imported' => $imported
                ], JSON_UNESCAPED_UNICODE);
                exit;
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูล INVS: ' . $e->getMessage()]);
                exit;
            }
        }

        // Action 2: Seed default Thai hospital/general units
        if ($action === 'seed_defaults') {
            $defaultUnits = [
                ['code' => 'U001', 'name' => 'ชิ้น'],
                ['code' => 'U002', 'name' => 'กล่อง'],
                ['code' => 'U003', 'name' => 'แผง'],
                ['code' => 'U004', 'name' => 'ขวด'],
                ['code' => 'U005', 'name' => 'หลอด'],
                ['code' => 'U006', 'name' => 'เม็ด'],
                ['code' => 'U007', 'name' => 'ซอง'],
                ['code' => 'U008', 'name' => 'ชุด'],
                ['code' => 'U009', 'name' => 'ม้วน'],
                ['code' => 'U010', 'name' => 'แพ็ค'],
                ['code' => 'U011', 'name' => 'ถุง'],
                ['code' => 'U012', 'name' => 'กิโลกรัม'],
                ['code' => 'U013', 'name' => 'ลิตร'],
                ['code' => 'U014', 'name' => 'มิลลิลิตร'],
                ['code' => 'U015', 'name' => 'แผ่น'],
                ['code' => 'U016', 'name' => 'เครื่อง'],
                ['code' => 'U017', 'name' => 'เล่ม'],
                ['code' => 'U018', 'name' => 'ใบ'],
                ['code' => 'U019', 'name' => 'อัน'],
                ['code' => 'U020', 'name' => 'คู่']
            ];

            $existing = $pdo2->query("SELECT name FROM inv_units")->fetchAll(PDO::FETCH_COLUMN);
            $existingMap = array_fill_keys(array_map('trim', $existing), true);

            $insStmt = $pdo2->prepare("INSERT INTO inv_units (code, name, active) VALUES (?, ?, 1)");
            $added = 0;

            foreach ($defaultUnits as $du) {
                if (!isset($existingMap[$du['name']])) {
                    $insStmt->execute([$du['code'], $du['name']]);
                    $existingMap[$du['name']] = true;
                    $added++;
                }
            }

            echo json_encode([
                'status' => 'success',
                'message' => "เพิ่มหน่วยนับมาตรฐานสำเร็จ {$added} รายการ",
                'added' => $added
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Standard Single Unit Create
        $code = trim($data['code'] ?? '');
        $name = trim($data['name'] ?? '');
        $active = isset($data['active']) ? (int)$data['active'] : 1;

        if (empty($name)) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุชื่อหน่วยนับ']);
            exit;
        }

        $chk = $pdo2->prepare("SELECT id FROM inv_units WHERE name = ?");
        $chk->execute([$name]);
        if ($chk->fetch()) {
            echo json_encode(['status' => 'error', 'message' => "ชื่อหน่วยนับนี้ ('{$name}') มีอยู่ในระบบแล้ว"]);
            exit;
        }

        $stmt = $pdo2->prepare("INSERT INTO inv_units (code, name, active) VALUES (?, ?, ?)");
        $stmt->execute([$code ?: null, $name, $active]);

        echo json_encode([
            'status' => 'success',
            'message' => 'เพิ่มหน่วยนับเรียบร้อยแล้ว',
            'id' => $pdo2->lastInsertId()
        ], JSON_UNESCAPED_UNICODE);

    } elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true) ?: [];
        $id = (int)($data['id'] ?? 0);
        $code = trim($data['code'] ?? '');
        $name = trim($data['name'] ?? '');
        $active = isset($data['active']) ? (int)$data['active'] : 1;

        if (!$id || empty($name)) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุ ID และชื่อหน่วยนับ']);
            exit;
        }

        $chk = $pdo2->prepare("SELECT id FROM inv_units WHERE name = ? AND id != ?");
        $chk->execute([$name, $id]);
        if ($chk->fetch()) {
            echo json_encode(['status' => 'error', 'message' => "ชื่อหน่วยนับนี้ ('{$name}') ซ้ำกับรายการอื่น"]);
            exit;
        }

        $stmt = $pdo2->prepare("UPDATE inv_units SET code = ?, name = ?, active = ? WHERE id = ?");
        $stmt->execute([$code ?: null, $name, $active, $id]);

        echo json_encode([
            'status' => 'success',
            'message' => 'อัปเดตข้อมูลหน่วยนับเรียบร้อยแล้ว'
        ], JSON_UNESCAPED_UNICODE);

    } elseif ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'Missing ID']);
            exit;
        }

        // Check if used in inv_items
        $chk = $pdo2->prepare("SELECT COUNT(*) FROM inv_items WHERE unit_id = ? OR purchase_unit_id = ? OR issue_unit_id = ?");
        $chk->execute([$id, $id, $id]);
        $count = (int)$chk->fetchColumn();

        if ($count > 0) {
            echo json_encode([
                'status' => 'error', 
                'message' => "ไม่สามารถลบได้ เนื่องจากมีรายการพัสดุใช้งานหน่วยนับนี้อยู่ ({$count} รายการ) แนะนำให้เปลี่ยนสถานะเป็น 'ปิดใช้งาน' แทน"
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $pdo2->prepare("DELETE FROM inv_units WHERE id = ?");
        $stmt->execute([$id]);

        echo json_encode([
            'status' => 'success',
            'message' => 'ลบหน่วยนับเรียบร้อยแล้ว'
        ], JSON_UNESCAPED_UNICODE);

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
