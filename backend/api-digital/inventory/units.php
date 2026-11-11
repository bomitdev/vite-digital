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
        $warehouse_id = isset($_GET['warehouse_id']) && $_GET['warehouse_id'] !== '' ? (int)$_GET['warehouse_id'] : 0;

        $itemCountSub = $warehouse_id > 0
            ? "(SELECT COUNT(*) FROM inv_items WHERE warehouse_id = " . (int)$warehouse_id . " AND (unit_id = u.id OR purchase_unit_id = u.id OR issue_unit_id = u.id))"
            : "(SELECT COUNT(*) FROM inv_items WHERE unit_id = u.id OR purchase_unit_id = u.id OR issue_unit_id = u.id)";

        $sql = "SELECT u.*, 
                       w.name as warehouse_name,
                       {$itemCountSub} as item_count 
                FROM inv_units u 
                LEFT JOIN inv_warehouses w ON u.warehouse_id = w.id
                WHERE 1=1";
        $params = [];

        if ($activeOnly) {
            $sql .= " AND u.active = 1";
        }

        if ($warehouse_id > 0) {
            $sql .= " AND u.warehouse_id = ?";
            $params[] = $warehouse_id;
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

        // Action 1: Import units from INVS (Disabled)
        if ($action === 'import_invs') {
            echo json_encode([
                'status' => 'error',
                'message' => 'การเชื่อมต่อระบบ INVS ถูกตัดการเชื่อมต่อแล้ว'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Action 2: Seed default Thai hospital/general units
        if ($action === 'seed_defaults') {
            $seedWarehouseId = !empty($data['warehouse_id']) ? (int)$data['warehouse_id'] : null;
            $defaultUnits = [
                ['name' => 'ชิ้น'],
                ['name' => 'กล่อง'],
                ['name' => 'แผง'],
                ['name' => 'ขวด'],
                ['name' => 'หลอด'],
                ['name' => 'เม็ด'],
                ['name' => 'ซอง'],
                ['name' => 'ชุด'],
                ['name' => 'ม้วน'],
                ['name' => 'แพ็ค'],
                ['name' => 'ถุง'],
                ['name' => 'กิโลกรัม'],
                ['name' => 'ลิตร'],
                ['name' => 'มิลลิลิตร'],
                ['name' => 'แผ่น'],
                ['name' => 'เครื่อง'],
                ['name' => 'เล่ม'],
                ['name' => 'ใบ'],
                ['name' => 'อัน'],
                ['name' => 'คู่']
            ];

            $chkExist = $pdo2->prepare("SELECT name FROM inv_units WHERE warehouse_id <=> ?");
            $chkExist->execute([$seedWarehouseId]);
            $existing = $chkExist->fetchAll(PDO::FETCH_COLUMN);
            $existingMap = array_fill_keys(array_map('trim', $existing), true);

            $stmtMax = $pdo2->prepare("SELECT MAX(CAST(SUBSTRING(code, 2) AS UNSIGNED)) as max_num FROM inv_units WHERE warehouse_id <=> ? AND code LIKE 'U%'");
            $stmtMax->execute([$seedWarehouseId]);
            $currentMax = (int)$stmtMax->fetchColumn();

            $insStmt = $pdo2->prepare("INSERT INTO inv_units (warehouse_id, code, name, active) VALUES (?, ?, ?, 1)");
            $added = 0;

            foreach ($defaultUnits as $du) {
                if (!isset($existingMap[$du['name']])) {
                    $currentMax++;
                    $unitCode = 'U' . str_pad($currentMax, 3, '0', STR_PAD_LEFT);
                    $insStmt->execute([$seedWarehouseId, $unitCode, $du['name']]);
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
        $warehouse_id = !empty($data['warehouse_id']) ? (int)$data['warehouse_id'] : null;
        $code = trim($data['code'] ?? '');
        $name = trim($data['name'] ?? '');
        $active = isset($data['active']) ? (int)$data['active'] : 1;

        if (empty($name)) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุชื่อหน่วยนับ']);
            exit;
        }

        if ($warehouse_id) {
            $chk = $pdo2->prepare("SELECT id FROM inv_units WHERE name = ? AND warehouse_id = ?");
            $chk->execute([$name, $warehouse_id]);
        } else {
            $chk = $pdo2->prepare("SELECT id FROM inv_units WHERE name = ? AND warehouse_id IS NULL");
            $chk->execute([$name]);
        }
        if ($chk->fetch()) {
            echo json_encode(['status' => 'error', 'message' => "ชื่อหน่วยนับนี้ ('{$name}') มีอยู่ในคลังนี้แล้ว"]);
            exit;
        }

        if (empty($code)) {
            $stmtMax = $pdo2->prepare("SELECT MAX(CAST(SUBSTRING(code, 2) AS UNSIGNED)) as max_num FROM inv_units WHERE warehouse_id <=> ? AND code LIKE 'U%'");
            $stmtMax->execute([$warehouse_id]);
            $currentMax = (int)$stmtMax->fetchColumn() + 1;
            $code = 'U' . str_pad($currentMax, 3, '0', STR_PAD_LEFT);
        }

        $stmt = $pdo2->prepare("INSERT INTO inv_units (warehouse_id, code, name, active) VALUES (?, ?, ?, ?)");
        $stmt->execute([$warehouse_id, $code ?: null, $name, $active]);

        echo json_encode([
            'status' => 'success',
            'message' => 'เพิ่มหน่วยนับเรียบร้อยแล้ว',
            'id' => $pdo2->lastInsertId()
        ], JSON_UNESCAPED_UNICODE);

    } elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true) ?: [];
        $id = (int)($data['id'] ?? 0);
        $warehouse_id = !empty($data['warehouse_id']) ? (int)$data['warehouse_id'] : null;
        $code = trim($data['code'] ?? '');
        $name = trim($data['name'] ?? '');
        $active = isset($data['active']) ? (int)$data['active'] : 1;

        if (!$id || empty($name)) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุ ID และชื่อหน่วยนับ']);
            exit;
        }

        if ($warehouse_id) {
            $chk = $pdo2->prepare("SELECT id FROM inv_units WHERE name = ? AND warehouse_id = ? AND id != ?");
            $chk->execute([$name, $warehouse_id, $id]);
        } else {
            $chk = $pdo2->prepare("SELECT id FROM inv_units WHERE name = ? AND warehouse_id IS NULL AND id != ?");
            $chk->execute([$name, $id]);
        }
        if ($chk->fetch()) {
            echo json_encode(['status' => 'error', 'message' => "ชื่อหน่วยนับนี้ ('{$name}') ซ้ำกับรายการอื่นในคลังนี้"]);
            exit;
        }

        $stmt = $pdo2->prepare("UPDATE inv_units SET warehouse_id = ?, code = ?, name = ?, active = ? WHERE id = ?");
        $stmt->execute([$warehouse_id, $code ?: null, $name, $active, $id]);

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
