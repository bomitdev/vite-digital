<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';
require_once __DIR__ . '/../../auth_utils.php';

header("Content-Type: application/json; charset=UTF-8");

$userData = authGuard();

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true) ?: $_POST;

$id = isset($input['id']) ? intval($input['id']) : 0;
$company_name = isset($input['company_name']) ? trim($input['company_name']) : '';

if (empty($company_name)) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุชื่อ แหล่งที่มา / ผู้จำหน่าย']);
    exit;
}

try {
    if ($id > 0) {
        $chkDup = $pdo2->prepare("SELECT id FROM mt_it_companies WHERE company_name = :name AND id != :id");
        $chkDup->execute([':name' => $company_name, ':id' => $id]);
        if ($chkDup->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'มีชื่อแหล่งที่มา/ผู้จำหน่ายนี้ในระบบแล้ว']);
            exit;
        }

        $getOld = $pdo2->prepare("SELECT company_name FROM mt_it_companies WHERE id = :id");
        $getOld->execute([':id' => $id]);
        $oldComp = $getOld->fetch(PDO::FETCH_ASSOC);

        if (!$oldComp) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลผู้จำหน่ายที่ต้องการแก้ไข']);
            exit;
        }

        $oldName = $oldComp['company_name'];

        $upd = $pdo2->prepare("UPDATE mt_it_companies SET company_name = :name WHERE id = :id");
        $upd->execute([
            ':name' => $company_name,
            ':id' => $id
        ]);

        if ($oldName !== $company_name) {
            $updLots = $pdo2->prepare("UPDATE mt_it_lots SET vendor = :newName WHERE vendor = :oldName");
            $updLots->execute([':newName' => $company_name, ':oldName' => $oldName]);

            $updTx = $pdo2->prepare("UPDATE mt_it_transactions SET reference_dest = :newName WHERE reference_dest = :oldName AND action_type = 'IN'");
            $updTx->execute([':newName' => $company_name, ':oldName' => $oldName]);
        }

        require_once __DIR__ . '/it_log_helper.php';
        insertItLog($pdo2, 'EDIT_COMPANY', "แก้ไขชื่อผู้จำหน่ายจาก '$oldName' เป็น '$company_name' (ID: $id)", $userData['name'] ?? 'System');

        echo json_encode(['status' => 'success', 'message' => 'แก้ไขชื่อผู้จำหน่ายเรียบร้อยแล้ว']);
    } else {
        $chkDup = $pdo2->prepare("SELECT id FROM mt_it_companies WHERE company_name = :name");
        $chkDup->execute([':name' => $company_name]);
        if ($chkDup->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'มีชื่อแหล่งที่มา/ผู้จำหน่ายนี้ในระบบแล้ว']);
            exit;
        }

        $ins = $pdo2->prepare("INSERT INTO mt_it_companies (company_name) VALUES (:name)");
        $ins->execute([':name' => $company_name]);
        $newId = $pdo2->lastInsertId();

        require_once __DIR__ . '/it_log_helper.php';
        insertItLog($pdo2, 'ADD_COMPANY', "เพิ่มผู้จำหน่ายใหม่ '$company_name' (ID: $newId)", $userData['name'] ?? 'System');

        echo json_encode(['status' => 'success', 'message' => 'เพิ่มผู้จำหน่ายเรียบร้อยแล้ว', 'id' => $newId]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดของฐานข้อมูล: ' . $e->getMessage()]);
}
