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
        // 2. If approved, refund stock, restore lots, and remove transaction
        if ($request['status'] === 'approved') {
            if (!$isAdmin) {
                $pdo2->rollBack();
                http_response_code(403);
                echo json_encode([
                    'success' => false, 
                    'message' => 'คำขอนี้ได้รับการอนุมัติและตัดจ่ายของแล้ว ไม่สามารถยกเลิกเองได้ กรุณาติดต่อผู้ดูแลคลัง'
                ]);
                exit;
            }

            $qty = (int)$request['quantity'];
            $matId = $request['material_id'];

            // Add back balance
            $stmtMat = $pdo2->prepare("UPDATE mt_it_materials SET balance = balance + :qty WHERE id = :matId");
            $stmtMat->execute([':qty' => $qty, ':matId' => $matId]);

            // Find matching OUT transactions
            $noteLike1 = 'Approved request ID ' . $request['id'] . '%';
            $noteLike2 = 'Approved request No: ' . $requestNo . '%';
            $stmtFindTx = $pdo2->prepare("SELECT id FROM mt_it_transactions WHERE material_id = :matId AND action_type = 'OUT' AND (note LIKE :note1 OR note LIKE :note2)");
            $stmtFindTx->execute([':matId' => $matId, ':note1' => $noteLike1, ':note2' => $noteLike2]);
            $txRows = $stmtFindTx->fetchAll(PDO::FETCH_ASSOC);

            $totalLotRestored = 0;
            if (!empty($txRows)) {
                $stmtTxLots = $pdo2->prepare("SELECT lot_id, quantity FROM mt_it_transaction_lots WHERE transaction_id = :tx_id");
                $stmtRestoreLot = $pdo2->prepare("UPDATE mt_it_lots SET remaining_qty = remaining_qty + :qty WHERE id = :lot_id");
                $stmtDelTxLot = $pdo2->prepare("DELETE FROM mt_it_transaction_lots WHERE transaction_id = :tx_id");
                $stmtDelTx = $pdo2->prepare("DELETE FROM mt_it_transactions WHERE id = :tx_id");

                foreach ($txRows as $txR) {
                    $stmtTxLots->execute([':tx_id' => $txR['id']]);
                    $usedLots = $stmtTxLots->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($usedLots as $ul) {
                        $stmtRestoreLot->execute([':qty' => $ul['quantity'], ':lot_id' => $ul['lot_id']]);
                        $totalLotRestored += (int)$ul['quantity'];
                    }
                    $stmtDelTxLot->execute([':tx_id' => $txR['id']]);
                    $stmtDelTx->execute([':tx_id' => $txR['id']]);
                }
            }

            // Fallback: If no lots were restored (legacy transaction or missing transaction_lots), restore lot to the latest active lot or create adjustment
            if ($totalLotRestored < $qty) {
                $missingLotQty = $qty - $totalLotRestored;
                $stmtLatestLot = $pdo2->prepare("SELECT id FROM mt_it_lots WHERE material_id = :mid ORDER BY receive_date DESC, id DESC LIMIT 1");
                $stmtLatestLot->execute([':mid' => $matId]);
                $latestLot = $stmtLatestLot->fetch(PDO::FETCH_ASSOC);

                if ($latestLot) {
                    $pdo2->prepare("UPDATE mt_it_lots SET remaining_qty = remaining_qty + :qty WHERE id = :id")
                         ->execute([':qty' => $missingLotQty, ':id' => $latestLot['id']]);
                } else {
                    $stmtPrice = $pdo2->prepare("SELECT price_per_unit FROM mt_it_materials WHERE id = :id");
                    $stmtPrice->execute([':id' => $matId]);
                    $pRow = $stmtPrice->fetch(PDO::FETCH_ASSOC);
                    $uPrice = $pRow ? floatval($pRow['price_per_unit']) : 0;

                    $pdo2->prepare("INSERT INTO mt_it_lots (material_id, lot_number, vendor, price_per_unit, original_qty, remaining_qty, receive_date) VALUES (:mid, 'LOT-RESTORED', 'คืนสต็อกจากยกเลิกคำขอ', :price, :orig, :rem, NOW())")
                         ->execute([':mid' => $matId, ':price' => $uPrice, ':orig' => $missingLotQty, ':rem' => $missingLotQty]);
                }
            }

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
