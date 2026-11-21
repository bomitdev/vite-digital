<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';
require_once __DIR__ . '/inv_auth_helper.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

function ensureExternalDepartmentsTable(PDO $pdo2): void {
    $pdo2->exec("
        CREATE TABLE IF NOT EXISTS inv_external_departments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(30) DEFAULT '',
            name VARCHAR(255) NOT NULL,
            head_name VARCHAR(255) DEFAULT '',
            head_position VARCHAR(255) DEFAULT '',
            active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $count = (int)$pdo2->query("SELECT COUNT(*) FROM inv_external_departments")->fetchColumn();
    if ($count === 0) {
        $seeds = [
            ['PCU01', 'รพ.สต.โคกเจริญ', 'นางลาวัลย์ กล่อมใจ', 'เจ้าพนักงานสาธารณสุขอาวุโส', 1],
            ['PCU02', 'รพ.สต.บุ่งเขียว', 'นายอิสระ หวานอ่อน', 'นักวิชาการสาธารณสุขปฏิบัติการ', 2],
            ['PCU03', 'รพ.สต.โคกก่ง', 'นายนัฐพงศ์ สารธิมา', 'นักวิชาการสาธารณสุขชำนาญการพิเศษ', 3],
            ['PCU04', 'รพ.สต.พุทธรักษา', 'นายทรงภพ แพงศรี', 'เจ้าพนักงานสาธารณสุขชำนาญงาน', 4],
            ['PCU05', 'รพ.สต.ห้วยฆ้อง', 'นายสมพงศ์ บุญจอง', 'นักวิชาการสาธารณสุขชำนาญการ', 5],
            ['PCU06', 'รพ.สต.ห้วยทม', 'นายสุริยา ดอกบัว', 'นักวิชาการสาธารณสุขชำนาญการ', 6],
            ['PCU07', 'รพ.สต.คำเดือย', 'นางทัศชัย จำปี', 'พยาบาลวิชาชีพชำนาญการ', 7],
            ['PCU08', 'รพ.สต.โนนกุง', 'นายสุริยะเดช ศรีชาเชษฐ์', 'นักวิชาการสาธารณสุขชำนาญการพิเศษ', 8],
            ['PCU09', 'รพ.สต.คำแก้วเมืองเก่า', 'นายรันดร ศรีริรมย์', 'นักวิชาการสาธารณสุขชำนาญการพิเศษ', 9],
        ];
        $stmt = $pdo2->prepare("
            INSERT INTO inv_external_departments (code, name, head_name, head_position, active, sort_order)
            VALUES (?, ?, ?, ?, 1, ?)
        ");
        foreach ($seeds as $row) {
            $stmt->execute($row);
        }
    }
}

function isExternalDeptAdmin(?PDO $pdo3): bool {
    $currentUser = getInvCurrentUser($pdo3);
    if (($currentUser['uid'] ?? 0) <= 0) {
        return false;
    }
    $perms = $currentUser['perms'] ?? [];
    return !empty(array_intersect($perms, ['administrator', 'menu_inventory_admin', 'menu_inventory_manage']));
}

try {
    ensureExternalDepartmentsTable($pdo2);
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $showAll = isset($_GET['all']) && (string)$_GET['all'] === '1';
        $sql = "SELECT id, code, name, head_name, head_position, active, sort_order FROM inv_external_departments";
        if (!$showAll) {
            $sql .= " WHERE active = 1";
        }
        $sql .= " ORDER BY sort_order ASC, id ASC";
        $rows = $pdo2->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $rows]);
        exit;
    }

    if (!isExternalDeptAdmin($pdo3 ?? null)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'เฉพาะผู้ดูแลระบบหรือผู้จัดการคลังเท่านั้น']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];

    if ($method === 'POST') {
        $name = trim((string)($input['name'] ?? ''));
        $code = trim((string)($input['code'] ?? ''));
        $headName = trim((string)($input['head_name'] ?? ''));
        $headPosition = trim((string)($input['head_position'] ?? ''));
        $active = isset($input['active']) ? ((int)$input['active'] ? 1 : 0) : 1;
        $sortOrder = (int)($input['sort_order'] ?? 0);

        if ($name === '') {
            echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุชื่อหน่วยงานภายนอก']);
            exit;
        }

        $stmt = $pdo2->prepare("
            INSERT INTO inv_external_departments (code, name, head_name, head_position, active, sort_order)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$code, $name, $headName, $headPosition, $active, $sortOrder]);
        echo json_encode(['status' => 'success', 'id' => (int)$pdo2->lastInsertId(), 'message' => 'เพิ่มหน่วยงานภายนอกเรียบร้อย']);
        exit;
    }

    if ($method === 'PUT') {
        $id = (int)($input['id'] ?? 0);
        $name = trim((string)($input['name'] ?? ''));
        $code = trim((string)($input['code'] ?? ''));
        $headName = trim((string)($input['head_name'] ?? ''));
        $headPosition = trim((string)($input['head_position'] ?? ''));
        $active = isset($input['active']) ? ((int)$input['active'] ? 1 : 0) : 1;
        $sortOrder = (int)($input['sort_order'] ?? 0);

        if ($id <= 0 || $name === '') {
            echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
            exit;
        }

        $stmt = $pdo2->prepare("
            UPDATE inv_external_departments
            SET code = ?, name = ?, head_name = ?, head_position = ?, active = ?, sort_order = ?
            WHERE id = ?
        ");
        $stmt->execute([$code, $name, $headName, $headPosition, $active, $sortOrder, $id]);
        echo json_encode(['status' => 'success', 'message' => 'แก้ไขหน่วยงานภายนอกเรียบร้อย']);
        exit;
    }

    if ($method === 'DELETE') {
        $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบรหัสหน่วยงาน']);
            exit;
        }
        $stmt = $pdo2->prepare("DELETE FROM inv_external_departments WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'ลบหน่วยงานภายนอกเรียบร้อย']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

