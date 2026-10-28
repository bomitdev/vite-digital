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

        // Action: Import from INVS SQL Server COMPANY table
        if ($action === 'import_invs') {
            $importWarehouseId = !empty($data['warehouse_id']) ? (int)$data['warehouse_id'] : null;
            $sqlSrvHost = "192.168.2.19, 1433";
            $sqlSrvDb   = "invs";
            $sqlSrvUser = "sa";
            $sqlSrvPass = "Cnm@10985";

            try {
                $connSqlSrv = new PDO("sqlsrv:server=$sqlSrvHost;Database=$sqlSrvDb", $sqlSrvUser, $sqlSrvPass);
                $connSqlSrv->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $q = "SELECT 
                        RTRIM(COMPANY_CODE) as vendor_code,
                        RTRIM(COMPANY_NAME) as vendor_name,
                        RTRIM(ADDRESS) as address1,
                        RTRIM(ADDRESS2) as address2,
                        RTRIM(TEL) as phone,
                        RTRIM(TAX_ID) as tax_id,
                        RTRIM(EMAIL) as email,
                        RTRIM(DETAILER) as contact_person,
                        RTRIM(BANK_NAME) as bank_name,
                        RTRIM(BANK_ACC_NO) as bank_acc_no,
                        RTRIM(BANK_ACC_NAME) as bank_acc_name
                      FROM COMPANY 
                      WHERE (HIDE = 'N' OR HIDE IS NULL) AND COMPANY_NAME IS NOT NULL AND LTRIM(RTRIM(COMPANY_NAME)) <> ''
                      ORDER BY COMPANY_NAME ASC";

                $s = $connSqlSrv->query($q);
                $invsCompanies = $s->fetchAll(PDO::FETCH_ASSOC);

                // Check existing codes & names
                $existingRows = $pdo2->query("SELECT vendor_code, vendor_name FROM inv_vendors")->fetchAll(PDO::FETCH_ASSOC);
                $existingCodes = [];
                $existingNames = [];
                foreach ($existingRows as $er) {
                    if (!empty($er['vendor_code'])) $existingCodes[trim($er['vendor_code'])] = true;
                    if (!empty($er['vendor_name'])) $existingNames[trim($er['vendor_name'])] = true;
                }

                $insStmt = $pdo2->prepare("
                    INSERT INTO inv_vendors 
                    (warehouse_id, vendor_code, vendor_name, tax_id, address, phone, email, contact_person, bank_info, active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
                ");

                $imported = 0;
                foreach ($invsCompanies as $c) {
                    $code = trim($c['vendor_code'] ?? '');
                    $name = trim($c['vendor_name'] ?? '');

                    if (empty($name)) continue;
                    if (isset($existingNames[$name])) continue;
                    if (!empty($code) && isset($existingCodes[$code])) continue;

                    $addr = trim(($c['address1'] ?? '') . ' ' . ($c['address2'] ?? ''));
                    $tax = trim($c['tax_id'] ?? '');
                    $phone = trim($c['phone'] ?? '');
                    $email = trim($c['email'] ?? '');
                    $contact = trim($c['contact_person'] ?? '');

                    $bankInfo = '';
                    if (!empty($c['bank_name']) || !empty($c['bank_acc_no'])) {
                        $bankInfo = trim(($c['bank_name'] ?? '') . ' ' . ($c['bank_acc_no'] ?? '') . ' ' . ($c['bank_acc_name'] ?? ''));
                    }

                    $insStmt->execute([
                        $importWarehouseId,
                        $code ?: null,
                        $name,
                        $tax ?: null,
                        $addr ?: null,
                        $phone ?: null,
                        $email ?: null,
                        $contact ?: null,
                        $bankInfo ?: null
                    ]);

                    if (!empty($code)) $existingCodes[$code] = true;
                    $existingNames[$name] = true;
                    $imported++;
                }

                echo json_encode([
                    'status' => 'success',
                    'message' => "นำเข้าข้อมูลบริษัทจาก INVS สำเร็จ {$imported} รายการ",
                    'imported_count' => $imported
                ], JSON_UNESCAPED_UNICODE);
                exit;

            } catch (Exception $e) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'ไม่สามารถเชื่อมต่อ INVS SQL Server ได้: ' . $e->getMessage()
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
        } else {
            // Check duplicate code
            $chk = $pdo2->prepare("SELECT id FROM inv_vendors WHERE vendor_code = ?");
            $chk->execute([$code]);
            if ($chk->fetch()) {
                echo json_encode(['status' => 'error', 'message' => "รหัสบริษัท {$code} มีอยู่ในระบบแล้ว"]);
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

        if (!empty($code)) {
            $chk = $pdo2->prepare("SELECT id FROM inv_vendors WHERE vendor_code = ? AND id != ?");
            $chk->execute([$code, $id]);
            if ($chk->fetch()) {
                echo json_encode(['status' => 'error', 'message' => "รหัสบริษัท {$code} ซ้ำกับรายการอื่น"]);
                exit;
            }
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

        // Check if used in PO
        $poCheck = $pdo2->prepare("SELECT COUNT(*) FROM inv_purchase_orders WHERE vendor_id = ?");
        $poCheck->execute([$id]);
        $usedCount = (int)$poCheck->fetchColumn();

        if ($usedCount > 0) {
            // Soft delete
            $stmt = $pdo2->prepare("UPDATE inv_vendors SET active = 0 WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode([
                'status' => 'success',
                'message' => 'ปิดการใช้งานบริษัทเรียบร้อยแล้ว (เนื่องจากมีการอ้างอิงในใบสั่งซื้อเดิม)'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            // Hard delete
            $stmt = $pdo2->prepare("DELETE FROM inv_vendors WHERE id = ?");
            $stmt->execute([$id]);
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
