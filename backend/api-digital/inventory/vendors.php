<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

/**
 * ค้นหารหัสบริษัทซ้ำภายในคลังเดียวกัน (หรือระดับ Global ถ้า warehouse_id เป็น null)
 * @param PDO $pdo2
 * @param string $code
 * @param int|null $warehouse_id
 * @param int|null $excludeId
 * @return array|false
 */
function findVendorByCodeInWarehouse(PDO $pdo2, string $code, ?int $warehouse_id, ?int $excludeId = null) {
    $sql = $warehouse_id
        ? "SELECT id, vendor_code, vendor_name FROM inv_vendors WHERE vendor_code = ? AND warehouse_id = ?"
        : "SELECT id, vendor_code, vendor_name FROM inv_vendors WHERE vendor_code = ? AND warehouse_id IS NULL";
    $params = $warehouse_id ? [$code, $warehouse_id] : [$code];
    if ($excludeId) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }
    $stmt = $pdo2->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

try {
    if ($method === 'GET') {
        $search = trim($_GET['search'] ?? '');
        $activeOnly = isset($_GET['active_only']) ? (int)$_GET['active_only'] : 0;
        $warehouse_id = isset($_GET['warehouse_id']) && $_GET['warehouse_id'] !== '' ? (int)$_GET['warehouse_id'] : 0;
        $strict = isset($_GET['strict']) ? (int)$_GET['strict'] : 0;

        $sql = "SELECT v.*, w.name as warehouse_name, w.code as warehouse_code 
                FROM inv_vendors v 
                LEFT JOIN inv_warehouses w ON v.warehouse_id = w.id 
                WHERE 1=1";
        $params = [];

        if ($activeOnly) {
            $sql .= " AND v.active = 1";
        }

        if ($warehouse_id > 0) {
            $includeGlobal = isset($_GET['include_global']) ? (int)$_GET['include_global'] : 0;
            if ($includeGlobal) {
                $sql .= " AND (v.warehouse_id = ? OR v.warehouse_id IS NULL)";
            } else {
                $sql .= " AND v.warehouse_id = ?";
            }
            $params[] = $warehouse_id;
        }

        if (!empty($search)) {
            $sql .= " AND (v.vendor_code LIKE ? OR v.vendor_name LIKE ? OR v.tax_id LIKE ? OR v.phone LIKE ? OR v.contact_person LIKE ?)";
            $s = "%$search%";
            $params = array_merge($params, [$s, $s, $s, $s, $s]);
        }

        $sql .= " ORDER BY v.vendor_name ASC";

        $stmt = $pdo2->prepare($sql);
        $stmt->execute($params);
        $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'data' => $vendors,
            'count' => count($vendors)
        ], JSON_UNESCAPED_UNICODE);

    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true) ?: $_POST;
        $action = trim($data['action'] ?? '');

        // Action: Import from INVS (Disabled)
        if ($action === 'import_invs') {
            echo json_encode([
                'status' => 'error',
                'message' => 'การเชื่อมต่อระบบ INVS ถูกตัดการเชื่อมต่อแล้ว'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Action: Batch Import from Excel / JSON
        if ($action === 'batch_import') {
            $items = $data['items'] ?? [];
            if (!is_array($items) || empty($items)) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่พบรายการข้อมูลสำหรับนำเข้า'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $targetWarehouseId = !empty($data['warehouse_id']) ? (int)$data['warehouse_id'] : null;
            $updateExisting = isset($data['update_existing']) ? (bool)$data['update_existing'] : true;

            $insertedCount = 0;
            $updatedCount = 0;
            $skippedCount = 0;
            $errors = [];

            // Pre-fetch max ID for auto-generating VND-xxxx codes
            $maxId = (int)$pdo2->query("SELECT COALESCE(MAX(id), 0) FROM inv_vendors")->fetchColumn();

            // Prepared statements
            $stmtFindByName = $pdo2->prepare("SELECT id, vendor_code, vendor_name FROM inv_vendors WHERE vendor_name = ? AND (warehouse_id = ? OR warehouse_id IS NULL)");
            $stmtFindByNameNoWh = $pdo2->prepare("SELECT id, vendor_code, vendor_name FROM inv_vendors WHERE vendor_name = ?");

            $stmtInsert = $pdo2->prepare("
                INSERT INTO inv_vendors 
                (warehouse_id, vendor_code, vendor_name, tax_id, address, phone, email, contact_person, bank_info, active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmtUpdate = $pdo2->prepare("
                UPDATE inv_vendors SET 
                    vendor_name = COALESCE(NULLIF(?, ''), vendor_name),
                    tax_id = COALESCE(NULLIF(?, ''), tax_id),
                    address = COALESCE(NULLIF(?, ''), address),
                    phone = COALESCE(NULLIF(?, ''), phone),
                    email = COALESCE(NULLIF(?, ''), email),
                    contact_person = COALESCE(NULLIF(?, ''), contact_person),
                    bank_info = COALESCE(NULLIF(?, ''), bank_info),
                    active = ?,
                    warehouse_id = COALESCE(?, warehouse_id)
                WHERE id = ?
            ");

            $pdo2->beginTransaction();

            try {
                foreach ($items as $idx => $item) {
                    $rowNum = $idx + 1;
                    $name = trim($item['vendor_name'] ?? '');
                    if (empty($name)) {
                        $errors[] = "แถวที่ {$rowNum}: ไม่พบชื่อบริษัท / ผู้จำหน่าย (ข้าม)";
                        $skippedCount++;
                        continue;
                    }

                    $code = trim($item['vendor_code'] ?? '');
                    $tax_id = trim($item['tax_id'] ?? '');
                    $phone = trim($item['phone'] ?? '');
                    $email = trim($item['email'] ?? '');
                    $contact = trim($item['contact_person'] ?? '');
                    $address = trim($item['address'] ?? '');
                    $bank = trim($item['bank_info'] ?? '');
                    $active = isset($item['active']) ? (int)$item['active'] : 1;
                    $whId = isset($item['warehouse_id']) && $item['warehouse_id'] !== '' ? (int)$item['warehouse_id'] : $targetWarehouseId;

                    $existingId = null;

                    // 1. Check if vendor_code exists in this warehouse
                    if (!empty($code)) {
                        $found = findVendorByCodeInWarehouse($pdo2, $code, $whId);
                        if ($found) {
                            $existingId = (int)$found['id'];
                        }
                    }

                    // 2. If no existing by code, check by name
                    if (!$existingId) {
                        if ($whId) {
                            $stmtFindByName->execute([$name, $whId]);
                            $found = $stmtFindByName->fetch(PDO::FETCH_ASSOC);
                        } else {
                            $stmtFindByNameNoWh->execute([$name]);
                            $found = $stmtFindByNameNoWh->fetch(PDO::FETCH_ASSOC);
                        }
                        if ($found) {
                            $existingId = (int)$found['id'];
                        }
                    }

                    if ($existingId) {
                        if ($updateExisting) {
                            $stmtUpdate->execute([
                                $name,
                                $tax_id ?: null,
                                $address ?: null,
                                $phone ?: null,
                                $email ?: null,
                                $contact ?: null,
                                $bank ?: null,
                                $active,
                                $whId ?: null,
                                $existingId
                            ]);
                            $updatedCount++;
                        } else {
                            $skippedCount++;
                        }
                    } else {
                        // Generate code if empty
                        if (empty($code)) {
                            $maxId++;
                            $code = 'VND-' . str_pad($maxId, 4, '0', STR_PAD_LEFT);
                            // Safety check code uniqueness within warehouse
                            while (findVendorByCodeInWarehouse($pdo2, $code, $whId)) {
                                $maxId++;
                                $code = 'VND-' . str_pad($maxId, 4, '0', STR_PAD_LEFT);
                            }
                        }

                        $stmtInsert->execute([
                            $whId ?: null,
                            $code,
                            $name,
                            $tax_id ?: null,
                            $address ?: null,
                            $phone ?: null,
                            $email ?: null,
                            $contact ?: null,
                            $bank ?: null,
                            $active
                        ]);
                        $insertedCount++;
                    }
                }

                $pdo2->commit();

                require_once __DIR__ . '/inv_log_helper.php';
                recordInvActivityLog($pdo2, [
                    'warehouse_id' => $targetWarehouseId ?? null,
                    'page_code' => 'settings',
                    'page_name' => 'ตั้งค่าระบบ (Master Data)',
                    'action' => 'BATCH_IMPORT_VENDORS',
                    'target_type' => 'vendor',
                    'details' => "นำเข้าผู้จำหน่ายแบบกลุ่ม (เพิ่มใหม่ {$insertedCount}, อัปเดต {$updatedCount}, ข้าม {$skippedCount})",
                    'meta' => ['inserted' => $insertedCount, 'updated' => $updatedCount, 'skipped' => $skippedCount],
                ], $pdo3 ?? null);

                echo json_encode([
                    'status' => 'success',
                    'message' => "นำเข้าข้อมูลบริษัทสำเร็จ (เพิ่มใหม่ {$insertedCount} รายการ, อัปเดต {$updatedCount} รายการ, ข้าม {$skippedCount} รายการ)",
                    'inserted_count' => $insertedCount,
                    'updated_count' => $updatedCount,
                    'skipped_count' => $skippedCount,
                    'errors' => $errors
                ], JSON_UNESCAPED_UNICODE);
                exit;

            } catch (Exception $e) {
                $pdo2->rollBack();
                echo json_encode([
                    'status' => 'error',
                    'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        // Action: Normal Insert Vendor
        $warehouse_id = !empty($data['warehouse_id']) ? (int)$data['warehouse_id'] : null;
        $name = trim($data['vendor_name'] ?? '');
        $code = trim($data['vendor_code'] ?? '');
        $tax_id = trim($data['tax_id'] ?? '');
        $address = trim($data['address'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $email = trim($data['email'] ?? '');
        $contact_person = trim($data['contact_person'] ?? '');
        $bank_info = trim($data['bank_info'] ?? '');
        $active = isset($data['active']) ? (int)$data['active'] : 1;

        if (empty($name)) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุชื่อบริษัท / ผู้จำหน่าย']);
            exit;
        }

        // Auto-generate code if empty
        if (empty($code)) {
            $nextId = (int)$pdo2->query("SELECT COALESCE(MAX(id), 0) + 1 FROM inv_vendors")->fetchColumn();
            $code = 'VND-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
            while (findVendorByCodeInWarehouse($pdo2, $code, $warehouse_id)) {
                $nextId++;
                $code = 'VND-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
            }
        } else {
            // Check duplicate code within warehouse
            if (findVendorByCodeInWarehouse($pdo2, $code, $warehouse_id)) {
                echo json_encode(['status' => 'error', 'message' => "รหัสบริษัท {$code} มีอยู่ในคลังนี้แล้ว"]);
                exit;
            }
        }

        $ins = $pdo2->prepare("
            INSERT INTO inv_vendors 
            (warehouse_id, vendor_code, vendor_name, tax_id, address, phone, email, contact_person, bank_info, active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([
            $warehouse_id,
            $code,
            $name,
            $tax_id ?: null,
            $address ?: null,
            $phone ?: null,
            $email ?: null,
            $contact_person ?: null,
            $bank_info ?: null,
            $active
        ]);

        $newId = (int)$pdo2->lastInsertId();
        require_once __DIR__ . '/inv_log_helper.php';
        recordInvActivityLog($pdo2, [
            'warehouse_id' => $warehouse_id,
            'page_code' => 'settings',
            'page_name' => 'ตั้งค่าระบบ (Master Data)',
            'action' => 'CREATE_VENDOR',
            'target_type' => 'vendor',
            'target_id' => (string)$newId,
            'details' => "เพิ่มบริษัทผู้จำหน่าย [{$code}] {$name}",
        ], $pdo3 ?? null);

        echo json_encode([
            'status' => 'success',
            'message' => 'เพิ่มข้อมูลบริษัทเรียบร้อยแล้ว',
            'id' => $newId
        ], JSON_UNESCAPED_UNICODE);

    } elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true) ?: [];
        $id = (int)($data['id'] ?? 0);
        $warehouse_id = !empty($data['warehouse_id']) ? (int)$data['warehouse_id'] : null;
        $name = trim($data['vendor_name'] ?? '');
        $code = trim($data['vendor_code'] ?? '');
        $tax_id = trim($data['tax_id'] ?? '');
        $address = trim($data['address'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $email = trim($data['email'] ?? '');
        $contact_person = trim($data['contact_person'] ?? '');
        $bank_info = trim($data['bank_info'] ?? '');
        $active = isset($data['active']) ? (int)$data['active'] : 1;

        if (!$id || empty($name)) {
            echo json_encode(['status' => 'error', 'message' => 'ID และชื่อบริษัทจำเป็นต้องระบุ']);
            exit;
        }

        if (!empty($code) && findVendorByCodeInWarehouse($pdo2, $code, $warehouse_id, $id)) {
            echo json_encode(['status' => 'error', 'message' => "รหัสบริษัท {$code} ซ้ำกับรายการอื่นในคลังนี้"]);
            exit;
        }

        $upd = $pdo2->prepare("
            UPDATE inv_vendors SET 
                warehouse_id = ?,
                vendor_code = ?,
                vendor_name = ?,
                tax_id = ?,
                address = ?,
                phone = ?,
                email = ?,
                contact_person = ?,
                bank_info = ?,
                active = ?
            WHERE id = ?
        ");
        $upd->execute([
            $warehouse_id,
            $code ?: null,
            $name,
            $tax_id ?: null,
            $address ?: null,
            $phone ?: null,
            $email ?: null,
            $contact_person ?: null,
            $bank_info ?: null,
            $active,
            $id
        ]);
        require_once __DIR__ . '/inv_log_helper.php';
        recordInvActivityLog($pdo2, [
            'warehouse_id' => $warehouse_id,
            'page_code' => 'settings',
            'page_name' => 'ตั้งค่าระบบ (Master Data)',
            'action' => 'UPDATE_VENDOR',
            'target_type' => 'vendor',
            'target_id' => (string)$id,
            'details' => "แก้ไขข้อมูลบริษัทผู้จำหน่าย [{$code}] {$name}",
        ], $pdo3 ?? null);

        echo json_encode([
            'status' => 'success',
            'message' => 'อัปเดตข้อมูลบริษัทเรียบร้อยแล้ว'
        ], JSON_UNESCAPED_UNICODE);

    } elseif ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'Missing ID']);
            exit;
        }

        $vStmt = $pdo2->prepare("SELECT warehouse_id, vendor_code, vendor_name FROM inv_vendors WHERE id = ?");
        $vStmt->execute([$id]);
        $vRow = $vStmt->fetch(PDO::FETCH_ASSOC);

        // Check if used in PO
        $poCheck = $pdo2->prepare("SELECT COUNT(*) FROM inv_purchase_orders WHERE vendor_id = ?");
        $poCheck->execute([$id]);
        $usedCount = (int)$poCheck->fetchColumn();

        if ($usedCount > 0) {
            // Soft delete
            $stmt = $pdo2->prepare("UPDATE inv_vendors SET active = 0 WHERE id = ?");
            $stmt->execute([$id]);
            require_once __DIR__ . '/inv_log_helper.php';
            recordInvActivityLog($pdo2, [
                'warehouse_id' => !empty($vRow['warehouse_id']) ? (int)$vRow['warehouse_id'] : null,
                'page_code' => 'settings',
                'page_name' => 'ตั้งค่าระบบ (Master Data)',
                'action' => 'DISABLE_VENDOR',
                'target_type' => 'vendor',
                'target_id' => (string)$id,
                'details' => "ปิดการใช้งานผู้จำหน่าย " . ($vRow ? "[{$vRow['vendor_code']}] {$vRow['vendor_name']}" : "#{$id}"),
            ], $pdo3 ?? null);
            echo json_encode([
                'status' => 'success',
                'message' => 'ปิดการใช้งานบริษัทเรียบร้อยแล้ว (เนื่องจากมีการอ้างอิงในใบสั่งซื้อเดิม)'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            // Hard delete
            $stmt = $pdo2->prepare("DELETE FROM inv_vendors WHERE id = ?");
            $stmt->execute([$id]);
            require_once __DIR__ . '/inv_log_helper.php';
            recordInvActivityLog($pdo2, [
                'warehouse_id' => !empty($vRow['warehouse_id']) ? (int)$vRow['warehouse_id'] : null,
                'page_code' => 'settings',
                'page_name' => 'ตั้งค่าระบบ (Master Data)',
                'action' => 'DELETE_VENDOR',
                'target_type' => 'vendor',
                'target_id' => (string)$id,
                'details' => "ลบข้อมูลบริษัทผู้จำหน่าย " . ($vRow ? "[{$vRow['vendor_code']}] {$vRow['vendor_name']}" : "#{$id}"),
            ], $pdo3 ?? null);
            echo json_encode([
                'status' => 'success',
                'message' => 'ลบข้อมูลบริษัทเรียบร้อยแล้ว'
            ], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
