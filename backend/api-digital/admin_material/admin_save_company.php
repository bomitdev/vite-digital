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
        // Mode: Update existing company
        // 1. Check duplicate name with other records
        $chkDup = $pdo2->prepare("SELECT id FROM mt_admin_companies WHERE company_name = :name AND id != :id");
        $chkDup->execute([':name' => $company_name, ':id' => $id]);
        if ($chkDup->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'มีชื่อแหล่งที่มา/ผู้จำหน่ายนี้ในระบบแล้ว']);
            exit;
        }

        // 2. Get old company name
        $getOld = $pdo2->prepare("SELECT company_name FROM mt_admin_companies WHERE id = :id");
        $getOld->execute([':id' => $id]);
        $oldComp = $getOld->fetch(PDO::FETCH_ASSOC);

        if (!$oldComp) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลผู้จำหน่ายที่ต้องการแก้ไข']);
            exit;
        }

        $oldName = $oldComp['company_name'];

        // 3. Update mt_admin_companies
        $upd = $pdo2->prepare("UPDATE mt_admin_companies SET company_name = :name WHERE id = :id");
        $upd->execute([
            ':name' => $company_name,
            ':id' => $id
        ]);

        // 4. Synchronize transactions and lots if name changed
        if ($oldName !== $company_name) {
            $updTx = $pdo2->prepare("UPDATE mt_admin_transactions SET reference_dest = :newName WHERE reference_dest = :oldName AND action_type = 'IN'");
            $updTx->execute([
                ':newName' => $company_name,
                ':oldName' => $oldName
            ]);

            $updLot = $pdo2->prepare("UPDATE mt_admin_lots SET vendor = :newName WHERE vendor = :oldName");
            $updLot->execute([
                ':newName' => $company_name,
                ':oldName' => $oldName
            ]);
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'อัปเดตข้อมูลผู้จำหน่ายเรียบร้อยแล้ว',
            'data' => [
                'id' => $id,
                'company_name' => $company_name
            ]
        ]);
        exit;
    }

    // Mode: Insert new company
    $stmt = $pdo2->prepare("SELECT id, company_name FROM mt_admin_companies WHERE company_name = :name");
    $stmt->execute([':name' => $company_name]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        echo json_encode([
            'status' => 'success',
            'message' => 'พบผู้จำหน่ายนี้ในระบบแล้ว',
            'data' => $existing
        ]);
        exit;
    }

    $ins = $pdo2->prepare("INSERT INTO mt_admin_companies (company_name) VALUES (:name)");
    $ins->execute([':name' => $company_name]);
    $newId = $pdo2->lastInsertId();

    echo json_encode([
        'status' => 'success',
        'message' => 'เพิ่มข้อมูลผู้จำหน่ายสำเร็จ',
        'data' => [
            'id' => $newId,
            'company_name' => $company_name
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'DB Error: ' . $e->getMessage()
    ]);
}
