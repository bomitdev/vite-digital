<?php
/**
 * Migration Script: Migrate Legacy Requisition Requests to Unified Inventory
 * Migrates:
 * 1. mt_it_requests -> inv_requisitions & inv_requisition_items (Warehouse 1: คลังวัสดุคอมพิวเตอร์)
 * 2. mt_admin_materials & mt_admin_requests -> inv_requisitions (Warehouse 3: คลังวัสดุบริหาร)
 */

declare(strict_types=1);
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

try {
    $pdo2->beginTransaction();

    $report = [
        'it_requests_migrated' => 0,
        'it_items_migrated' => 0,
        'admin_materials_synced' => 0,
        'admin_requests_migrated' => 0,
        'admin_items_migrated' => 0,
        'skipped_duplicates' => 0
    ];

    // Helper to get or create unit
    $getOrCreateUnit = function($unitName) use ($pdo2) {
        $unitName = trim($unitName ?: 'หน่วย');
        $stmt = $pdo2->prepare("SELECT id FROM inv_units WHERE name = ? LIMIT 1");
        $stmt->execute([$unitName]);
        $uid = $stmt->fetchColumn();
        if ($uid) return (int)$uid;

        $stmtIns = $pdo2->prepare("INSERT INTO inv_units (name) VALUES (?)");
        $stmtIns->execute([$unitName]);
        return (int)$pdo2->lastInsertId();
    };

    // =========================================================================
    // PART 1: Migrate IT Material Requests (Warehouse ID: 1)
    // =========================================================================
    $stmtItMatMap = $pdo2->query("
        SELECT m.id as old_id, m.code, m.name, m.unit, i.id as new_id, i.item_code, i.item_name, u.name as unit_name
        FROM mt_it_materials m
        LEFT JOIN inv_items i ON (i.old_code = m.code OR i.item_code = m.code) AND i.warehouse_id = 1
        LEFT JOIN inv_units u ON i.unit_id = u.id
    ");
    $itMatMap = [];
    foreach ($stmtItMatMap->fetchAll(PDO::FETCH_ASSOC) as $m) {
        $itMatMap[$m['old_id']] = $m;
    }

    $stmtItReqs = $pdo2->query("SELECT * FROM mt_it_requests ORDER BY id ASC");
    $itRows = $stmtItReqs->fetchAll(PDO::FETCH_ASSOC);

    // Group by request_no
    $itGroups = [];
    foreach ($itRows as $r) {
        $rNo = trim($r['request_no'] ?? '');
        $grpKey = !empty($rNo) ? $rNo : ('REQ-IT-OLD-' . $r['id']);
        if (!isset($itGroups[$grpKey])) {
            $itGroups[$grpKey] = [
                'req_no' => $grpKey,
                'request_date' => !empty($r['created_at']) ? $r['created_at'] : ($r['request_date'] . ' 08:30:00'),
                'department_name' => trim($r['department'] ?? 'ไม่ระบุกลุ่มงาน'),
                'requester_name' => trim($r['requester_name'] ?? 'เจ้าหน้าที่'),
                'status' => strtoupper($r['status'] ?? 'PENDING'),
                'admin_note' => trim($r['admin_note'] ?? ''),
                'created_at' => $r['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => $r['updated_at'] ?? date('Y-m-d H:i:s'),
                'items' => []
            ];
        }
        $itGroups[$grpKey]['items'][] = $r;
    }

    $stmtCheckReq = $pdo2->prepare("SELECT id FROM inv_requisitions WHERE req_no = ?");
    $stmtInsReq = $pdo2->prepare("
        INSERT INTO inv_requisitions 
        (req_no, warehouse_id, request_date, department_name, requester_name, purpose, status, approver_name, approved_date, admin_note, created_at, updated_at) 
        VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtInsReqItem = $pdo2->prepare("
        INSERT INTO inv_requisition_items 
        (requisition_id, item_id, item_code, item_name, unit_name, qty_requested, qty_approved, status, item_note, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($itGroups as $grp) {
        $stmtCheckReq->execute([$grp['req_no']]);
        if ($stmtCheckReq->fetch()) {
            $report['skipped_duplicates']++;
            continue;
        }

        $st = $grp['status'];
        if (!in_array($st, ['PENDING', 'APPROVED', 'REJECTED'])) {
            $st = 'PENDING';
        }

        $approverName = ($st === 'APPROVED') ? 'เจ้าหน้าที่คลังคอมฯ' : null;
        $approvedDate = ($st === 'APPROVED') ? $grp['updated_at'] : null;

        $stmtInsReq->execute([
            $grp['req_no'],
            $grp['request_date'],
            $grp['department_name'],
            $grp['requester_name'],
            'ขอเบิกวัสดุคอมพิวเตอร์ (ดึงมาจากระบบเดิม)',
            $st,
            $approverName,
            $approvedDate,
            $grp['admin_note'],
            $grp['created_at'],
            $grp['updated_at']
        ]);
        $newReqId = (int)$pdo2->lastInsertId();
        $report['it_requests_migrated']++;

        foreach ($grp['items'] as $itemRow) {
            $oldMatId = (int)$itemRow['material_id'];
            $map = $itMatMap[$oldMatId] ?? null;

            $itemId = $map['new_id'] ?? 0;
            $itemCode = $map['item_code'] ?? ($map['code'] ?? 'IT-N/A');
            $itemName = $map['item_name'] ?? ($map['name'] ?? 'วัสดุคอมพิวเตอร์');
            $unitName = $map['unit_name'] ?? ($map['unit'] ?? 'หน่วย');

            // If item not yet in inv_items, create item
            if (!$itemId) {
                $uid = $getOrCreateUnit($unitName);
                $stmtNewIt = $pdo2->prepare("
                    INSERT INTO inv_items (warehouse_id, item_code, old_code, item_name, unit_id, active)
                    VALUES (1, ?, ?, ?, ?, 1)
                ");
                $stmtNewIt->execute([$itemCode, $itemCode, $itemName, $uid]);
                $itemId = (int)$pdo2->lastInsertId();
            }

            $qtyReq = (float)($itemRow['quantity'] ?? 1);
            $qtyApp = ($st === 'APPROVED') ? $qtyReq : 0;

            $stmtInsReqItem->execute([
                $newReqId,
                $itemId,
                $itemCode,
                $itemName,
                $unitName,
                $qtyReq,
                $qtyApp,
                $st,
                $itemRow['admin_note'] ?? '',
                $itemRow['created_at'] ?? date('Y-m-d H:i:s')
            ]);
            $report['it_items_migrated']++;
        }
    }


    // =========================================================================
    // PART 2: Sync Admin Materials & Migrate Requests (Warehouse ID: 3)
    // =========================================================================
    // Ensure Category for Warehouse 3
    $stmtAdminCat = $pdo2->prepare("SELECT id FROM inv_categories WHERE warehouse_id = 3 LIMIT 1");
    $stmtAdminCat->execute();
    $adminCatId = $stmtAdminCat->fetchColumn();
    if (!$adminCatId) {
        $stmtInsCat = $pdo2->prepare("INSERT INTO inv_categories (warehouse_id, code, name) VALUES (3, 'ADM01', 'วัสดุสำนักงานและงานบริหาร')");
        $stmtInsCat->execute();
        $adminCatId = (int)$pdo2->lastInsertId();
    }

    // Sync mt_admin_materials into inv_items for Warehouse 3
    $stmtOldAdminMat = $pdo2->query("SELECT * FROM mt_admin_materials");
    $oldAdminMats = $stmtOldAdminMat->fetchAll(PDO::FETCH_ASSOC);

    $stmtFindWh3Item = $pdo2->prepare("SELECT id FROM inv_items WHERE warehouse_id = 3 AND (item_code = ? OR old_code = ?) LIMIT 1");
    $stmtInsWh3Item = $pdo2->prepare("
        INSERT INTO inv_items (warehouse_id, category_id, item_code, old_code, item_name, unit_id, standard_price, minimum_stock, active)
        VALUES (3, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    $stmtInsBalWh3 = $pdo2->prepare("INSERT IGNORE INTO inv_stock_balances (warehouse_id, item_id, balance_qty) VALUES (3, ?, ?)");
    $stmtInsLotWh3 = $pdo2->prepare("
        INSERT INTO inv_stock_lots (warehouse_id, item_id, lot_number, unit_cost, receive_qty, balance_qty)
        VALUES (3, ?, 'LOT-LEGACY', ?, ?, ?)
    ");

    $adminMatMap = [];

    foreach ($oldAdminMats as $am) {
        $code = trim($am['code']);
        $stmtFindWh3Item->execute([$code, $code]);
        $existingId = $stmtFindWh3Item->fetchColumn();

        if (!$existingId) {
            $uid = $getOrCreateUnit($am['unit']);
            $price = (float)($am['price_per_unit'] ?? 0);
            $minAlert = (int)($am['min_alert'] ?? 5);
            $balance = (float)($am['balance'] ?? 0);

            $stmtInsWh3Item->execute([
                $adminCatId,
                $code,
                $code,
                $am['name'],
                $uid,
                $price,
                $minAlert
            ]);
            $newItemId = (int)$pdo2->lastInsertId();
            $report['admin_materials_synced']++;

            // Create initial balance & lot
            if ($balance > 0) {
                $stmtInsBalWh3->execute([$newItemId, $balance]);
                $stmtInsLotWh3->execute([$newItemId, $price, $balance, $balance]);
            }

            $adminMatMap[$am['id']] = [
                'new_id' => $newItemId,
                'item_code' => $code,
                'item_name' => $am['name'],
                'unit_name' => $am['unit']
            ];
        } else {
            $adminMatMap[$am['id']] = [
                'new_id' => (int)$existingId,
                'item_code' => $code,
                'item_name' => $am['name'],
                'unit_name' => $am['unit']
            ];
        }
    }

    // Now migrate mt_admin_requests
    $stmtAdminReqs = $pdo2->query("SELECT * FROM mt_admin_requests ORDER BY id ASC");
    $adminRows = $stmtAdminReqs->fetchAll(PDO::FETCH_ASSOC);

    $adminGroups = [];
    foreach ($adminRows as $ar) {
        $rNo = trim($ar['request_no'] ?? '');
        $grpKey = !empty($rNo) ? $rNo : ('REQ-ADM-OLD-' . $ar['id']);
        if (!isset($adminGroups[$grpKey])) {
            $adminGroups[$grpKey] = [
                'req_no' => $grpKey,
                'request_date' => !empty($ar['created_at']) ? $ar['created_at'] : ($ar['request_date'] . ' 08:30:00'),
                'department_name' => trim($ar['department'] ?? 'ไม่ระบุกลุ่มงาน'),
                'requester_name' => trim($ar['requester_name'] ?? 'เจ้าหน้าที่'),
                'status' => strtoupper($ar['status'] ?? 'PENDING'),
                'admin_note' => trim($ar['admin_note'] ?? ''),
                'created_at' => $ar['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => $ar['updated_at'] ?? date('Y-m-d H:i:s'),
                'items' => []
            ];
        }
        $adminGroups[$grpKey]['items'][] = $ar;
    }

    $stmtInsAdminReq = $pdo2->prepare("
        INSERT INTO inv_requisitions 
        (req_no, warehouse_id, request_date, department_name, requester_name, purpose, status, approver_name, approved_date, admin_note, created_at, updated_at) 
        VALUES (?, 3, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($adminGroups as $grp) {
        $stmtCheckReq->execute([$grp['req_no']]);
        if ($stmtCheckReq->fetch()) {
            $report['skipped_duplicates']++;
            continue;
        }

        $st = $grp['status'];
        if (!in_array($st, ['PENDING', 'APPROVED', 'REJECTED'])) {
            $st = 'PENDING';
        }

        $approverName = ($st === 'APPROVED') ? 'เจ้าหน้าที่คลังบริหาร' : null;
        $approvedDate = ($st === 'APPROVED') ? $grp['updated_at'] : null;

        $stmtInsAdminReq->execute([
            $grp['req_no'],
            $grp['request_date'],
            $grp['department_name'],
            $grp['requester_name'],
            'ขอเบิกวัสดุบริหารทั่วไป (ดึงมาจากระบบเดิม)',
            $st,
            $approverName,
            $approvedDate,
            $grp['admin_note'],
            $grp['created_at'],
            $grp['updated_at']
        ]);
        $newReqId = (int)$pdo2->lastInsertId();
        $report['admin_requests_migrated']++;

        foreach ($grp['items'] as $itemRow) {
            $oldMatId = (int)$itemRow['material_id'];
            $map = $adminMatMap[$oldMatId] ?? null;

            $itemId = $map['new_id'] ?? 0;
            $itemCode = $map['item_code'] ?? 'ADM-N/A';
            $itemName = $map['item_name'] ?? 'วัสดุบริหาร';
            $unitName = $map['unit_name'] ?? 'หน่วย';

            $qtyReq = (float)($itemRow['quantity'] ?? 1);
            $qtyApp = ($st === 'APPROVED') ? $qtyReq : 0;

            $stmtInsReqItem->execute([
                $newReqId,
                $itemId,
                $itemCode,
                $itemName,
                $unitName,
                $qtyReq,
                $qtyApp,
                $st,
                $itemRow['admin_note'] ?? '',
                $itemRow['created_at'] ?? date('Y-m-d H:i:s')
            ]);
            $report['admin_items_migrated']++;
        }
    }

    $pdo2->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'ดึงรายการคำขอเบิกจากระบบเดิมเข้าสู่ระบบใหม่เรียบร้อยแล้ว',
        'report' => $report
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
