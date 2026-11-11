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
        $warehouse_id = isset($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : 0;
        
        $sql = "SELECT t.*, 
                       (SELECT COUNT(*) FROM inv_items i WHERE i.item_type_id = t.id) as item_count
                FROM inv_item_types t ";
        $params = [];

        if ($warehouse_id > 0) {
            $sql .= "WHERE t.warehouse_id = ? ";
            $params[] = $warehouse_id;
        }
        $sql .= "ORDER BY CAST(t.code AS UNSIGNED) ASC, t.code ASC";

        $stmt = $pdo2->prepare($sql);
        $stmt->execute($params);
        $types = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'data' => $types], JSON_UNESCAPED_UNICODE);

    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true) ?: $_POST;
        $code = trim($data['code'] ?? '');
        $name = trim($data['name'] ?? '');
        $report_group = trim($data['report_group'] ?? '');
        $moph_report_group = trim($data['moph_report_group'] ?? '');
        $ap_account_code = trim($data['ap_account_code'] ?? '');
        $debit_account_code = trim($data['debit_account_code'] ?? '');
        $credit_account_code = trim($data['credit_account_code'] ?? '');
        $warehouse_id = !empty($data['warehouse_id']) ? (int)$data['warehouse_id'] : null;
        $active = isset($data['active']) ? (int)$data['active'] : 1;

        if (!$code || !$name) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกรหัสและชื่อประเภทเวชภัณฑ์']);
            exit;
        }

        $chk = $pdo2->prepare("SELECT id FROM inv_item_types WHERE code = ?");
        $chk->execute([$code]);
        if ($chk->fetch()) {
            echo json_encode(['status' => 'error', 'message' => "รหัสประเภทเวชภัณฑ์นี้ ({$code}) มีอยู่ในระบบแล้ว"]);
            exit;
        }

        $stmt = $pdo2->prepare("INSERT INTO inv_item_types (code, name, report_group, moph_report_group, ap_account_code, debit_account_code, credit_account_code, warehouse_id, active)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$code, $name, $report_group, $moph_report_group, $ap_account_code, $debit_account_code, $credit_account_code, $warehouse_id, $active]);
        
        echo json_encode(['status' => 'success', 'message' => 'เพิ่มประเภทเวชภัณฑ์เรียบร้อยแล้ว'], JSON_UNESCAPED_UNICODE);

    } elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true) ?: [];
        $id = (int)($data['id'] ?? 0);
        $code = trim($data['code'] ?? '');
        $name = trim($data['name'] ?? '');
        $report_group = trim($data['report_group'] ?? '');
        $moph_report_group = trim($data['moph_report_group'] ?? '');
        $ap_account_code = trim($data['ap_account_code'] ?? '');
        $debit_account_code = trim($data['debit_account_code'] ?? '');
        $credit_account_code = trim($data['credit_account_code'] ?? '');
        $warehouse_id = !empty($data['warehouse_id']) ? (int)$data['warehouse_id'] : null;
        $active = isset($data['active']) ? (int)$data['active'] : 1;

        if (!$id || !$code || !$name) {
            echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน (ต้องระบุ ID, รหัส และชื่อ)']);
            exit;
        }

        $chk = $pdo2->prepare("SELECT id FROM inv_item_types WHERE code = ? AND id != ?");
        $chk->execute([$code, $id]);
        if ($chk->fetch()) {
            echo json_encode(['status' => 'error', 'message' => "รหัสประเภทเวชภัณฑ์นี้ ({$code}) ซ้ำกับรายการอื่น"]);
            exit;
        }

        $stmt = $pdo2->prepare("UPDATE inv_item_types SET 
                                    code = ?, 
                                    name = ?, 
                                    report_group = ?, 
                                    moph_report_group = ?, 
                                    ap_account_code = ?, 
                                    debit_account_code = ?, 
                                    credit_account_code = ?, 
                                    warehouse_id = ?, 
                                    active = ? 
                                WHERE id = ?");
        $stmt->execute([$code, $name, $report_group, $moph_report_group, $ap_account_code, $debit_account_code, $credit_account_code, $warehouse_id, $active, $id]);

        echo json_encode(['status' => 'success', 'message' => 'อัปเดตประเภทเวชภัณฑ์เรียบร้อยแล้ว'], JSON_UNESCAPED_UNICODE);

    } elseif ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'Missing ID']);
            exit;
        }

        $chk = $pdo2->prepare("SELECT COUNT(*) FROM inv_items WHERE item_type_id = ?");
        $chk->execute([$id]);
        if ((int)$chk->fetchColumn() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบได้ เนื่องจากมีรายการพัสดุ/เวชภัณฑ์ที่ผูกกับประเภทนี้อยู่']);
            exit;
        }

        $stmt = $pdo2->prepare("DELETE FROM inv_item_types WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'ลบประเภทเวชภัณฑ์เรียบร้อยแล้ว'], JSON_UNESCAPED_UNICODE);

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

