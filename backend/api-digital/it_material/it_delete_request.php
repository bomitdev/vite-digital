<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';

// Secure Auth
$userData = authGuard();

if (!isset($pdo2)) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->request_no)) {
    echo json_encode(['success' => false, 'message' => 'Missing Request No field']);
    exit;
}

$requestNo = $data->request_no;
$isLegacy = strpos($requestNo, 'LEGACY-') === 0;

try {
    $pdo2->beginTransaction();

    if ($isLegacy) {
        $id = str_replace('LEGACY-', '', $requestNo);
        $stmt = $pdo2->prepare("SELECT * FROM mt_it_requests WHERE id = :id FOR UPDATE");
        $stmt->execute([':id' => $id]);
    } else {
        $stmt = $pdo2->prepare("SELECT * FROM mt_it_requests WHERE request_no = :request_no FOR UPDATE");
        $stmt->execute([':request_no' => $requestNo]);
    }

    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($requests)) {
        $pdo2->rollBack();
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit;
    }

    // Check ownership or admin rights (ใครเบิกคนนั้นสามารถยกเลิกได้)
    $isOwner = false;
    $isAdmin = false;
    $currentUid = isset($userData['uid']) ? intval($userData['uid']) : 0;

    // Check if user has admin rights
    if (isset($pdo3) && $currentUid > 0) {
        $stmtRights = $pdo3->prepare("SELECT access_user FROM hr_person WHERE ID = :id");
        $stmtRights->execute([':id' => $currentUid]);
        $userRow = $stmtRights->fetch(PDO::FETCH_ASSOC);
        if ($userRow && !empty($userRow['access_user'])) {
            $accessList = explode(':', $userRow['access_user']);
            if (in_array('administrator', $accessList) || in_array('menu_it_material_manage', $accessList)) {
                $isAdmin = true;
            }
        }
    }

    $firstReq = $requests[0];
    if ($currentUid > 0 && !empty($firstReq['requester_id']) && intval($firstReq['requester_id']) === $currentUid) {
        $isOwner = true;
    }

    if (!$isOwner) {
        $currentName = trim($userData['name'] ?? '');
        $existingName = trim($firstReq['requester_name'] ?? '');

        if (!empty($currentName) && !empty($existingName)) {
            $cleanCurrent = preg_replace('/^(นาย|นางสาว|นาง|ดร\.|นพ\.|พญ\.|ทพ\.|ทพญ\.|ภก\.|ภญ\.|ว่าที่\s*ร\.ต\.|ร\.ต\.|อ\.)\s*/u', '', $currentName);
            $cleanCurrent = preg_replace('/\s+/', '', $cleanCurrent);

            $cleanExisting = preg_replace('/^(นาย|นางสาว|นาง|ดร\.|นพ\.|พญ\.|ทพ\.|ทพญ\.|ภก\.|ภญ\.|ว่าที่\s*ร\.ต\.|ร\.ต\.|อ\.)\s*/u', '', $existingName);
            $cleanExisting = preg_replace('/\s+/', '', $cleanExisting);

            if ($cleanCurrent === $cleanExisting || $currentName === $existingName) {
                $isOwner = true;
            }
        }
    }

    if (!$isOwner && isset($pdo3) && $currentUid > 0) {
        $stmtHr = $pdo3->prepare("
            SELECT CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as fullname,
                   CONCAT(p.HR_FNAME, ' ', p.HR_LNAME) as name_no_prefix
            FROM hr_person p
            LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
            WHERE p.ID = :id
        ");
        $stmtHr->execute([':id' => $currentUid]);
        $hrUser = $stmtHr->fetch(PDO::FETCH_ASSOC);
        if ($hrUser) {
            $existingName = trim($firstReq['requester_name'] ?? '');
            if ($existingName === trim($hrUser['fullname']) || $existingName === trim($hrUser['name_no_prefix'])) {
                $isOwner = true;
            }
        }
    }

    if (!$isOwner && !$isAdmin) {
        $pdo2->rollBack();
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'message' => 'คุณสามารถยกเลิกได้เฉพาะคำขอเบิกของตัวเองเท่านั้น'
        ]);
        exit;
    }

    $refundedCount = 0;
    foreach ($requests as $request) {
        // 2. If approved, refund stock and remove transaction
        if ($request['status'] === 'approved') {
            $qty = (int)$request['quantity'];
            $matId = $request['material_id'];

            // Add back balance
            $stmtMat = $pdo2->prepare("UPDATE mt_it_materials SET balance = balance + :qty WHERE id = :matId");
            $stmtMat->execute([':qty' => $qty, ':matId' => $matId]);

            // Remove the transaction OUT that was created (Match both legacy and new note styles)
            $noteLike1 = 'Approved request ID ' . $request['id'] . '%';
            $noteLike2 = 'Approved request No: ' . $requestNo . '%';
            $stmtDelTx = $pdo2->prepare("DELETE FROM mt_it_transactions WHERE material_id = :matId AND action_type = 'OUT' AND (note LIKE :note1 OR note LIKE :note2)");
            $stmtDelTx->execute([':matId' => $matId, ':note1' => $noteLike1, ':note2' => $noteLike2]);

            $refundedCount++;
        }

        // 3. Delete the request
        $stmtDel = $pdo2->prepare("DELETE FROM mt_it_requests WHERE id = :id");
        $stmtDel->execute([':id' => $request['id']]);
    }

    $pdo2->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Material request deleted' . ($refundedCount > 0 ? " and $refundedCount items refunded" : '')
    ]);
} catch (PDOException $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
