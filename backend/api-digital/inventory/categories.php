<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $warehouse_id = isset($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : 0;
        
        $sql = "SELECT c.*, w.name as warehouse_name 
                FROM inv_categories c 
                LEFT JOIN inv_warehouses w ON c.warehouse_id = w.id ";
        $params = [];

        if ($warehouse_id > 0) {
            $sql .= "WHERE c.warehouse_id = ? ";
            $params[] = $warehouse_id;
        }
        $sql .= "ORDER BY w.code ASC, c.code ASC";

        $stmt = $pdo2->prepare($sql);
        $stmt->execute($params);
        $categories = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'data' => $categories]);
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true) ?: $_POST;
        $warehouse_id = (int)($data['warehouse_id'] ?? 0);
        $code = trim($data['code'] ?? '');
        $name = trim($data['name'] ?? '');
        $active = isset($data['active']) ? (int)$data['active'] : 1;

        if (!$warehouse_id || !$code || !$name) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลคลัง, รหัสหมวดหมู่ และชื่อหมวดหมู่ให้ครบถ้วน']);
            exit;
        }

        $chk = $pdo2->prepare("SELECT id FROM inv_categories WHERE warehouse_id = ? AND code = ?");
        $chk->execute([$warehouse_id, $code]);
        if ($chk->fetch()) {
            echo json_encode(['status' => 'error', 'message' => "รหัสหมวดหมู่นี้ ({$code}) มีอยู่ในคลังนี้แล้ว"]);
            exit;
        }

        $stmt = $pdo2->prepare("INSERT INTO inv_categories (warehouse_id, code, name, active) VALUES (?, ?, ?, ?)");
        $stmt->execute([$warehouse_id, $code, $name, $active]);
        echo json_encode(['status' => 'success', 'message' => 'บันทึกข้อมูลหมวดหมู่เรียบร้อยแล้ว']);
    } elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true) ?: [];
        $id = (int)($data['id'] ?? 0);
        $warehouse_id = (int)($data['warehouse_id'] ?? 0);
        $code = trim($data['code'] ?? '');
        $name = trim($data['name'] ?? '');
        $active = isset($data['active']) ? (int)$data['active'] : 1;

        if (!$id || !$warehouse_id || !$code || !$name) {
            echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน (ต้องระบุ ID, คลัง, รหัส และชื่อหมวดหมู่)']);
            exit;
        }

        $chk = $pdo2->prepare("SELECT id FROM inv_categories WHERE warehouse_id = ? AND code = ? AND id != ?");
        $chk->execute([$warehouse_id, $code, $id]);
        if ($chk->fetch()) {
            echo json_encode(['status' => 'error', 'message' => "รหัสหมวดหมู่นี้ ({$code}) ซ้ำกับรายการอื่นในคลังนี้"]);
            exit;
        }

        $stmt = $pdo2->prepare("UPDATE inv_categories SET warehouse_id=?, code=?, name=?, active=? WHERE id=?");
        $stmt->execute([$warehouse_id, $code, $name, $active, $id]);
        echo json_encode(['status' => 'success', 'message' => 'อัปเดตข้อมูลหมวดหมู่เรียบร้อยแล้ว']);
    } elseif ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'Missing ID']);
            exit;
        }

        $chk = $pdo2->prepare("SELECT COUNT(*) FROM inv_items WHERE category_id = ?");
        $chk->execute([$id]);
        if ((int)$chk->fetchColumn() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบได้ เนื่องจากมีรายการพัสดุใช้งานหมวดหมู่นี้อยู่']);
            exit;
        }

        $stmt = $pdo2->prepare("DELETE FROM inv_categories WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'ลบหมวดหมู่เรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
