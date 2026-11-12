<?php
require_once '../../config.php';
require_once '../../cors.php';
require_once __DIR__ . '/inv_log_helper.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

/**
 * คำนวณรหัสวัสดุถัดไปอัตโนมัติ
 * @param PDO $pdo2
 * @param int|null $warehouse_id
 * @param int|null $category_id
 * @return string
 */
function getNextItemCode(PDO $pdo2, ?int $warehouse_id = 0, ?int $category_id = 0): string {
    if (!$warehouse_id) return 'C001';

    $categoryPrefix = '';
    if ($category_id > 0) {
        $catStmt = $pdo2->prepare("SELECT code FROM inv_categories WHERE id = ?");
        $catStmt->execute([$category_id]);
        $catCode = trim($catStmt->fetchColumn() ?: '');
        if ($catCode) {
            if (preg_match('/^([A-Za-z]+)/', $catCode, $cm)) {
                $categoryPrefix = strtoupper($cm[1]);
            }
        }
    }

    $sql = "SELECT item_code FROM inv_items WHERE warehouse_id = ? AND item_code IS NOT NULL AND item_code != '' ORDER BY id DESC";
    $stmt = $pdo2->prepare($sql);
    $stmt->execute([$warehouse_id]);
    $codes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($codes)) {
        if (!empty($categoryPrefix)) {
            return $categoryPrefix . '001';
        }
        $whStmt = $pdo2->prepare("SELECT code, name FROM inv_warehouses WHERE id = ?");
        $whStmt->execute([$warehouse_id]);
        $wh = $whStmt->fetch(PDO::FETCH_ASSOC);
        if ($wh && (strpos($wh['name'], 'คอม') !== false || $wh['code'] == '01')) {
            return 'C001';
        }
        return 'M001';
    }

    $targetPrefix = '';
    $sampleCode = '';

    if (!empty($categoryPrefix)) {
        foreach ($codes as $c) {
            if (stripos(trim($c), $categoryPrefix) === 0) {
                $targetPrefix = $categoryPrefix;
                $sampleCode = trim($c);
                break;
            }
        }
    }

    if (empty($targetPrefix)) {
        $sampleCode = trim($codes[0]);
    }

    if (preg_match('/^(.*?)(\d+)([^\d]*)$/', $sampleCode, $matches)) {
        $prefix = $matches[1];
        $digits = $matches[2];
        $suffix = $matches[3];
        $padLen = strlen($digits);

        $maxNum = 0;
        foreach ($codes as $c) {
            $c = trim($c);
            if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)' . preg_quote($suffix, '/') . '$/i', $c, $cm)) {
                $numVal = (int)$cm[1];
                if ($numVal > $maxNum) {
                    $maxNum = $numVal;
                    if (strlen($cm[1]) > $padLen) {
                        $padLen = strlen($cm[1]);
                    }
                }
            }
        }

        $nextNum = $maxNum + 1;
        $nextDigits = str_pad($nextNum, max($padLen, strlen((string)$nextNum)), '0', STR_PAD_LEFT);
        return $prefix . $nextDigits . $suffix;
    }

    return $sampleCode . '-001';
}

/**
 * แปลงค่า Flag แบบ 3 สถานะ (1 = ใช่/ส่ง, 0 = ไม่ใช่/ไม่ส่ง, null = ไม่ระบุ)
 * @param mixed $val
 * @return int|null
 */
function parseTriStateFlag($val): ?int {
    if ($val === null || $val === '') return null;
    if (is_bool($val)) return $val ? 1 : 0;
    if (is_numeric($val)) return ((int)$val) === 1 ? 1 : 0;
    $str = mb_strtolower(trim((string)$val));
    if ($str === '') return null;
    if (in_array($str, ['1', 'true', 'yes', 'ใช่', 'ส่งข้อมูลให้กระทรวง', 'ส่งข้อมูลให้dos', 'ส่ง', 'จ่ายฟรี'], true)) {
        return 1;
    }
    if (in_array($str, ['0', 'false', 'no', 'ไม่ใช่', 'ไม่ส่ง'], true)) {
        return 0;
    }
    return null;
}

/**
 * เตรียมและผูกฟิลด์เฉพาะทางเภสัชกรรมและกระทรวงสำหรับ POST / PUT
 * @param PDO $pdo2
 * @param array $data
 * @return array
 */
function resolvePharmacyPayload(PDO $pdo2, array $data): array {
    $dosage_form_id = !empty($data['dosage_form_id']) ? (int)$data['dosage_form_id'] : null;
    $ed_list_id = !empty($data['ed_list_id']) ? (int)$data['ed_list_id'] : null;
    $product_cat_id = !empty($data['product_cat_id']) ? (int)$data['product_cat_id'] : null;

    $dosage_form = trim($data['dosage_form'] ?? '');
    if ($dosage_form_id && empty($dosage_form)) {
        $st = $pdo2->prepare("SELECT code FROM inv_dosage_forms WHERE id = ?");
        $st->execute([$dosage_form_id]);
        $dosage_form = $st->fetchColumn() ?: '';
    }
    $ed_sub_list = trim($data['ed_sub_list'] ?? '');
    if ($ed_list_id && empty($ed_sub_list)) {
        $st = $pdo2->prepare("SELECT code FROM inv_ed_lists WHERE id = ?");
        $st->execute([$ed_list_id]);
        $ed_sub_list = $st->fetchColumn() ?: '';
    }
    $product_cat = trim($data['product_cat'] ?? '');
    if ($product_cat_id && empty($product_cat)) {
        $st = $pdo2->prepare("SELECT name FROM inv_product_categories WHERE id = ?");
        $st->execute([$product_cat_id]);
        $product_cat = $st->fetchColumn() ?: '';
    }

    return [
        'dosage_form_id' => $dosage_form_id,
        'ed_list_id' => $ed_list_id,
        'product_cat_id' => $product_cat_id,
        'std_code_24' => trim($data['std_code_24'] ?? ($data['old_code'] ?? '')),
        'tmt_gpu' => trim($data['tmt_gpu'] ?? ($data['barcode'] ?? '')),
        'strength' => trim($data['strength'] ?? ''),
        'is_gpo' => parseTriStateFlag($data['is_gpo'] ?? 0) ?? 0,
        'is_original' => parseTriStateFlag($data['is_original'] ?? 0) ?? 0,
        'send_moph' => parseTriStateFlag($data['send_moph'] ?? null),
        'send_dos' => parseTriStateFlag($data['send_dos'] ?? 0) ?? 0,
        'is_free_dispense' => parseTriStateFlag($data['is_free_dispense'] ?? 0) ?? 0,
        'packing_size' => isset($data['packing_size']) && $data['packing_size'] !== '' ? (int)$data['packing_size'] : 1,
        'std_unit_en' => trim($data['std_unit_en'] ?? ''),
        'std_unit_th' => trim($data['std_unit_th'] ?? ''),
        'moph_status_note' => trim($data['moph_status_note'] ?? ''),
        'moph_updated_text' => trim($data['moph_updated_text'] ?? ''),
        'dosage_form' => $dosage_form,
        'ed_sub_list' => $ed_sub_list,
        'product_cat' => $product_cat,
    ];
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $action = trim($_GET['action'] ?? '');
        $warehouse_id = isset($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : 0;
        $category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

        if ($action === 'next_code') {
            $nextCode = getNextItemCode($pdo2, $warehouse_id, $category_id);
            echo json_encode(['status' => 'success', 'next_code' => $nextCode]);
            exit;
        }
        
        $whOnHandSub = $warehouse_id > 0 
            ? "COALESCE((SELECT SUM(b.balance_qty) FROM inv_stock_balances b WHERE b.item_id = i.id AND b.warehouse_id = " . (int)$warehouse_id . "), 0)"
            : "COALESCE((SELECT SUM(b.balance_qty) FROM inv_stock_balances b WHERE b.item_id = i.id), 0)";

        $sql = "SELECT i.*, 
                       w.name as warehouse_name, 
                       c.name as category_name,
                       u.name as unit_name,
                       t.name as item_type_name,
                       t.code as item_type_code,
                       t.moph_report_group as item_type_moph,
                       df.code as dosage_form_code,
                       df.name as dosage_form_name,
                       ed.code as ed_list_code,
                       ed.name as ed_list_name,
                       pc.code as product_cat_code,
                       pc.name as product_cat_name,
                       $whOnHandSub as on_hand
                FROM inv_items i 
                LEFT JOIN inv_warehouses w ON i.warehouse_id = w.id
                LEFT JOIN inv_categories c ON i.category_id = c.id
                LEFT JOIN inv_units u ON i.unit_id = u.id
                LEFT JOIN inv_item_types t ON i.item_type_id = t.id
                LEFT JOIN inv_dosage_forms df ON i.dosage_form_id = df.id
                LEFT JOIN inv_ed_lists ed ON i.ed_list_id = ed.id
                LEFT JOIN inv_product_categories pc ON i.product_cat_id = pc.id
                WHERE 1=1 ";
        
        $params = [];

        if ($warehouse_id > 0) {
            $sql .= "AND i.warehouse_id = ? ";
            $params[] = $warehouse_id;
        }
        if ($category_id > 0) {
            $sql .= "AND i.category_id = ? ";
            $params[] = $category_id;
        }
        
        $sql .= "ORDER BY i.item_code ASC";

        $stmt = $pdo2->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $items]);

    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true) ?: $_POST;
        $action = trim($data['action'] ?? '');

        // Action: Batch Import Items from Excel / JSON
        if ($action === 'batch_import') {
            $warehouse_id = (int)($data['warehouse_id'] ?? 0);
            $items = $data['items'] ?? [];
            $updateExisting = isset($data['update_existing']) ? (bool)$data['update_existing'] : true;

            if (!$warehouse_id) {
                echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุคลังพัสดุเป้าหมาย (warehouse_id)'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if (!is_array($items) || empty($items)) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่พบรายการข้อมูลสำหรับนำเข้า'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Cache units for this warehouse & global
            $stmtUnits = $pdo2->prepare("SELECT id, name, code FROM inv_units WHERE warehouse_id = ? OR warehouse_id IS NULL");
            $stmtUnits->execute([$warehouse_id]);
            $unitCache = [];
            foreach ($stmtUnits->fetchAll(PDO::FETCH_ASSOC) as $u) {
                $unitCache[mb_strtolower(trim($u['name']))] = (int)$u['id'];
                if (!empty($u['code'])) {
                    $unitCache[mb_strtolower(trim($u['code']))] = (int)$u['id'];
                }
            }

            // Helper to get or create unit
            $stmtCreateUnit = $pdo2->prepare("INSERT INTO inv_units (warehouse_id, code, name, active) VALUES (?, ?, ?, 1)");
            $stmtMaxUnitCode = $pdo2->prepare("SELECT code FROM inv_units WHERE warehouse_id = ? AND code LIKE 'U%' ORDER BY id DESC LIMIT 1");

            // Cache categories for this warehouse
            $stmtCats = $pdo2->prepare("SELECT id, name, code FROM inv_categories WHERE warehouse_id = ?");
            $stmtCats->execute([$warehouse_id]);
            $catCache = [];
            foreach ($stmtCats->fetchAll(PDO::FETCH_ASSOC) as $c) {
                $catCache[mb_strtolower(trim($c['name']))] = (int)$c['id'];
                if (!empty($c['code'])) {
                    $catCache[mb_strtolower(trim($c['code']))] = (int)$c['id'];
                }
            }

            // Cache item types (prefer warehouse-specific then global)
            $stmtTypes = $pdo2->prepare("SELECT id, name, code, moph_report_group FROM inv_item_types WHERE active = 1 AND (warehouse_id = ? OR warehouse_id IS NULL)");
            $stmtTypes->execute([$warehouse_id]);
            $typeCache = [];
            foreach ($stmtTypes->fetchAll(PDO::FETCH_ASSOC) as $t) {
                $typeCache[mb_strtolower(trim($t['name']))] = (int)$t['id'];
                if (!empty($t['code'])) {
                    $typeCache[mb_strtolower(trim($t['code']))] = (int)$t['id'];
                }
                if (!empty($t['moph_report_group'])) {
                    $typeCache[mb_strtolower(trim($t['moph_report_group']))] = (int)$t['id'];
                }
            }

            // Cache pharmacy masters (dosage forms, ed lists, product categories)
            $dfCache = [];
            foreach ($pdo2->query("SELECT id, code, name FROM inv_dosage_forms WHERE active = 1")->fetchAll(PDO::FETCH_ASSOC) as $df) {
                $dfCache[mb_strtolower(trim($df['code']))] = ['id' => (int)$df['id'], 'code' => $df['code']];
                $dfCache[mb_strtolower(trim($df['name']))] = ['id' => (int)$df['id'], 'code' => $df['code']];
            }
            $edCache = [];
            foreach ($pdo2->query("SELECT id, code, name FROM inv_ed_lists WHERE active = 1")->fetchAll(PDO::FETCH_ASSOC) as $ed) {
                $edCache[mb_strtolower(trim($ed['code']))] = ['id' => (int)$ed['id'], 'code' => $ed['code']];
                $edCache[mb_strtolower(trim($ed['name']))] = ['id' => (int)$ed['id'], 'code' => $ed['code']];
            }
            $pcCache = [];
            foreach ($pdo2->query("SELECT id, code, name FROM inv_product_categories WHERE active = 1")->fetchAll(PDO::FETCH_ASSOC) as $pc) {
                $pcCache[mb_strtolower(trim($pc['code']))] = ['id' => (int)$pc['id'], 'name' => $pc['name']];
                $pcCache[mb_strtolower(trim($pc['name']))] = ['id' => (int)$pc['id'], 'name' => $pc['name']];
            }

            // Prepared statements for items
            $stmtFindByCode = $pdo2->prepare("SELECT id, item_code, item_name FROM inv_items WHERE warehouse_id = ? AND item_code = ?");
            $stmtFindByName = $pdo2->prepare("SELECT id, item_code, item_name FROM inv_items WHERE warehouse_id = ? AND item_name = ?");

            $stmtInsert = $pdo2->prepare("
                INSERT INTO inv_items (
                    item_code, old_code, std_code_24, item_name, item_name_en, category_id, item_type_id, warehouse_id, 
                    unit_id, purchase_unit_id, issue_unit_id, conversion_rate, packing_size, std_unit_en, std_unit_th,
                    barcode, tmt_gpu, brand, model, dosage_form, strength,
                    dosage_form_id, ed_list_id, ed_sub_list, product_cat_id, product_cat,
                    minimum_stock, maximum_stock, reorder_point, standard_price, last_purchase_price, ave_rate,
                    active, lot_control, expiry_control, serial_control,
                    is_gpo, is_original, send_moph, send_dos, is_free_dispense, moph_status_note, moph_updated_text
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?
                )
            ");

            $stmtUpdate = $pdo2->prepare("
                UPDATE inv_items SET 
                    item_name = COALESCE(NULLIF(?, ''), item_name),
                    item_name_en = COALESCE(NULLIF(?, ''), item_name_en),
                    old_code = COALESCE(NULLIF(?, ''), old_code),
                    std_code_24 = COALESCE(NULLIF(?, ''), std_code_24),
                    category_id = COALESCE(?, category_id),
                    item_type_id = COALESCE(?, item_type_id),
                    unit_id = COALESCE(?, unit_id),
                    packing_size = COALESCE(?, packing_size),
                    std_unit_en = COALESCE(NULLIF(?, ''), std_unit_en),
                    std_unit_th = COALESCE(NULLIF(?, ''), std_unit_th),
                    standard_price = ?,
                    last_purchase_price = ?,
                    minimum_stock = ?,
                    maximum_stock = ?,
                    reorder_point = ?,
                    lot_control = ?,
                    expiry_control = ?,
                    barcode = COALESCE(NULLIF(?, ''), barcode),
                    tmt_gpu = COALESCE(NULLIF(?, ''), tmt_gpu),
                    brand = COALESCE(NULLIF(?, ''), brand),
                    model = COALESCE(NULLIF(?, ''), model),
                    dosage_form = COALESCE(NULLIF(?, ''), dosage_form),
                    dosage_form_id = COALESCE(?, dosage_form_id),
                    strength = COALESCE(NULLIF(?, ''), strength),
                    ed_list_id = COALESCE(?, ed_list_id),
                    ed_sub_list = COALESCE(NULLIF(?, ''), ed_sub_list),
                    product_cat_id = COALESCE(?, product_cat_id),
                    product_cat = COALESCE(NULLIF(?, ''), product_cat),
                    is_gpo = COALESCE(?, is_gpo),
                    is_original = COALESCE(?, is_original),
                    send_moph = COALESCE(?, send_moph),
                    send_dos = COALESCE(?, send_dos),
                    is_free_dispense = COALESCE(?, is_free_dispense),
                    moph_status_note = COALESCE(NULLIF(?, ''), moph_status_note),
                    moph_updated_text = COALESCE(NULLIF(?, ''), moph_updated_text),
                    active = ?
                WHERE id = ?
            ");

            $insertedCount = 0;
            $updatedCount = 0;
            $skippedCount = 0;
            $errors = [];

            $pdo2->beginTransaction();

            try {
                foreach ($items as $idx => $row) {
                    $rowNum = $idx + 1;
                    $name = trim($row['item_name'] ?? '');
                    if (empty($name)) {
                        $errors[] = "แถวที่ {$rowNum}: ไม่มีชื่อวัสดุ/ยา (ข้าม)";
                        $skippedCount++;
                        continue;
                    }

                    // 1. Resolve unit_id
                    $unitName = trim($row['unit_name'] ?? $row['unit'] ?? '');
                    $unitKey = mb_strtolower($unitName);
                    $unitId = null;

                    if (!empty($unitKey) && isset($unitCache[$unitKey])) {
                        $unitId = $unitCache[$unitKey];
                    } elseif (!empty($unitName)) {
                        // Auto-create unit for this warehouse!
                        $stmtMaxUnitCode->execute([$warehouse_id]);
                        $lastCode = $stmtMaxUnitCode->fetchColumn() ?: '';
                        $nextNum = 1;
                        if (preg_match('/U(\d+)/i', $lastCode, $ucm)) {
                            $nextNum = (int)$ucm[1] + 1;
                        }
                        $newUnitCode = 'U' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
                        $stmtCreateUnit->execute([$warehouse_id, $newUnitCode, $unitName]);
                        $unitId = (int)$pdo2->lastInsertId();
                        $unitCache[$unitKey] = $unitId;
                    } else {
                        // Fallback default unit (e.g. first available unit or 'ชิ้น')
                        if (!empty($unitCache)) {
                            $unitId = reset($unitCache);
                        } else {
                            $stmtCreateUnit->execute([$warehouse_id, 'U001', 'ชิ้น']);
                            $unitId = (int)$pdo2->lastInsertId();
                            $unitCache['ชิ้น'] = $unitId;
                        }
                    }

                    // 2. Resolve category_id
                    $catName = trim($row['category_name'] ?? $row['category'] ?? $row['group_name'] ?? '');
                    $catKey = mb_strtolower($catName);
                    $categoryId = null;
                    if (!empty($catKey) && isset($catCache[$catKey])) {
                        $categoryId = $catCache[$catKey];
                    } elseif (!empty($row['category_id'])) {
                        $categoryId = (int)$row['category_id'];
                    }

                    // 3. Resolve item_type_id
                    $typeName = trim($row['item_type_name'] ?? $row['item_type'] ?? '');
                    $typeKey = mb_strtolower($typeName);
                    $itemTypeId = null;
                    if (!empty($typeKey) && isset($typeCache[$typeKey])) {
                        $itemTypeId = $typeCache[$typeKey];
                    } elseif (!empty($row['item_type_id'])) {
                        $itemTypeId = (int)$row['item_type_id'];
                    }

                    $code = trim($row['item_code'] ?? '');
                    $oldCode = trim($row['old_code'] ?? '');
                    $stdCode24 = trim($row['std_code_24'] ?? '');
                    if (empty($stdCode24) && !empty($oldCode)) {
                        $stdCode24 = $oldCode;
                    } elseif (empty($oldCode) && !empty($stdCode24)) {
                        $oldCode = $stdCode24;
                    }
                    $nameEn = trim($row['item_name_en'] ?? '');
                    $stdPrice = isset($row['standard_price']) && $row['standard_price'] !== '' ? (float)$row['standard_price'] : 0.00;
                    $lastPrice = isset($row['last_purchase_price']) && $row['last_purchase_price'] !== '' ? (float)$row['last_purchase_price'] : $stdPrice;
                    $aveRate = isset($row['ave_rate']) && $row['ave_rate'] !== '' ? (float)$row['ave_rate'] : 0.00;
                    $minStock = isset($row['minimum_stock']) && $row['minimum_stock'] !== '' ? (int)$row['minimum_stock'] : 0;
                    $maxStock = isset($row['maximum_stock']) && $row['maximum_stock'] !== '' ? (int)$row['maximum_stock'] : 0;
                    $reorderPoint = isset($row['reorder_point']) && $row['reorder_point'] !== '' ? (int)$row['reorder_point'] : $minStock;
                    $packingSize = isset($row['packing_size']) && $row['packing_size'] !== '' ? (int)$row['packing_size'] : 1;
                    $stdUnitEn = trim($row['std_unit_en'] ?? '');
                    $stdUnitTh = trim($row['std_unit_th'] ?? '');
                    
                    // Controls (Lot / Expiry)
                    $lotControl = parseTriStateFlag($row['lot_control'] ?? 0) ?? 0;
                    $expiryControl = parseTriStateFlag($row['expiry_control'] ?? 0) ?? 0;
                    $barcode = trim($row['barcode'] ?? '');
                    $tmtGpu = trim($row['tmt_gpu'] ?? '');
                    if (empty($tmtGpu) && !empty($barcode)) {
                        $tmtGpu = $barcode;
                    } elseif (empty($barcode) && !empty($tmtGpu)) {
                        $barcode = $tmtGpu;
                    }
                    $brand = trim($row['brand'] ?? '');
                    $model = trim($row['model'] ?? '');
                    $strength = trim($row['strength'] ?? '');
                    $active = isset($row['active']) ? (int)$row['active'] : 1;

                    // Resolve Pharmacy Masters (Dosage Form, ED List, FDA Product Category)
                    $dfRaw = trim($row['dosage_form'] ?? '');
                    $dfAliases = ['cr' => 'cre', 'jel' => 'gel', 'วดถ' => 'วถด'];
                    $dfLookup = $dfAliases[mb_strtolower($dfRaw)] ?? mb_strtolower($dfRaw);
                    $dosageFormId = !empty($row['dosage_form_id']) ? (int)$row['dosage_form_id'] : null;
                    $dosageForm = $dfRaw;
                    if (!$dosageFormId && $dfLookup !== '' && isset($dfCache[$dfLookup])) {
                        $dosageFormId = $dfCache[$dfLookup]['id'];
                        $dosageForm = $dfCache[$dfLookup]['code'];
                    }

                    $edRaw = trim($row['ed_sub_list'] ?? '');
                    $edKey = mb_strtolower($edRaw);
                    $edListId = !empty($row['ed_list_id']) ? (int)$row['ed_list_id'] : null;
                    $edSubList = $edRaw;
                    if (!$edListId && $edKey !== '' && isset($edCache[$edKey])) {
                        $edListId = $edCache[$edKey]['id'];
                        $edSubList = $edCache[$edKey]['code'];
                    }

                    $pcRaw = trim($row['product_cat'] ?? '');
                    $pcKey = mb_strtolower($pcRaw);
                    $productCatId = !empty($row['product_cat_id']) ? (int)$row['product_cat_id'] : null;
                    $productCat = $pcRaw;
                    if (!$productCatId && $pcKey !== '' && isset($pcCache[$pcKey])) {
                        $productCatId = $pcCache[$pcKey]['id'];
                        $productCat = $pcCache[$pcKey]['name'];
                    }

                    // Pharmacy & Ministry Flags
                    $isGpo = isset($row['is_gpo']) && $row['is_gpo'] !== '' ? parseTriStateFlag($row['is_gpo']) : null;
                    $isOriginal = isset($row['is_original']) && $row['is_original'] !== '' ? parseTriStateFlag($row['is_original']) : null;
                    $sendMoph = array_key_exists('send_moph', $row) ? parseTriStateFlag($row['send_moph']) : null;
                    $sendDos = isset($row['send_dos']) && $row['send_dos'] !== '' ? parseTriStateFlag($row['send_dos']) : null;
                    $isFreeDispense = isset($row['is_free_dispense']) && $row['is_free_dispense'] !== '' ? parseTriStateFlag($row['is_free_dispense']) : null;
                    $mophStatusNote = trim($row['moph_status_note'] ?? '');
                    $mophUpdatedText = trim($row['moph_updated_text'] ?? '');

                    // Check existing
                    $existingId = null;
                    if (!empty($code)) {
                        $stmtFindByCode->execute([$warehouse_id, $code]);
                        $found = $stmtFindByCode->fetch(PDO::FETCH_ASSOC);
                        if ($found) {
                            $existingId = (int)$found['id'];
                        }
                    }

                    if (!$existingId) {
                        $stmtFindByName->execute([$warehouse_id, $name]);
                        $found = $stmtFindByName->fetch(PDO::FETCH_ASSOC);
                        if ($found) {
                            $existingId = (int)$found['id'];
                        }
                    }

                    if ($existingId) {
                        if ($updateExisting) {
                            $stmtUpdate->execute([
                                $name,
                                $nameEn ?: null,
                                $oldCode ?: null,
                                $stdCode24 ?: null,
                                $categoryId ?: null,
                                $itemTypeId ?: null,
                                $unitId,
                                $packingSize,
                                $stdUnitEn ?: null,
                                $stdUnitTh ?: null,
                                $stdPrice,
                                $lastPrice,
                                $minStock,
                                $maxStock,
                                $reorderPoint,
                                $lotControl,
                                $expiryControl,
                                $barcode ?: null,
                                $tmtGpu ?: null,
                                $brand ?: null,
                                $model ?: null,
                                $dosageForm ?: null,
                                $dosageFormId,
                                $strength ?: null,
                                $edListId,
                                $edSubList ?: null,
                                $productCatId,
                                $productCat ?: null,
                                $isGpo,
                                $isOriginal,
                                $sendMoph,
                                $sendDos,
                                $isFreeDispense,
                                $mophStatusNote ?: null,
                                $mophUpdatedText ?: null,
                                $active,
                                $existingId
                            ]);
                            $updatedCount++;
                        } else {
                            $skippedCount++;
                        }
                    } else {
                        // Generate code if empty or ensure uniqueness within warehouse
                        if (empty($code)) {
                            $baseCode = getNextItemCode($pdo2, $warehouse_id, $categoryId);
                            $code = $baseCode;
                            $stmtFindByCode->execute([$warehouse_id, $code]);
                            $counter = 1;
                            while ($stmtFindByCode->fetch()) {
                                $code = $baseCode . '-' . $counter;
                                $stmtFindByCode->execute([$warehouse_id, $code]);
                                $counter++;
                            }
                        } else {
                            // Check warehouse-scoped collision
                            $stmtFindByCode->execute([$warehouse_id, $code]);
                            if ($stmtFindByCode->fetch()) {
                                $code = $code . '-' . ($rowNum);
                            }
                        }

                        $stmtInsert->execute([
                            $code,
                            $oldCode ?: null,
                            $stdCode24 ?: null,
                            $name,
                            $nameEn ?: null,
                            $categoryId ?: null,
                            $itemTypeId ?: null,
                            $warehouse_id,
                            $unitId,
                            $unitId, // default purchase_unit_id
                            $unitId, // default issue_unit_id
                            1.0000,
                            $packingSize,
                            $stdUnitEn ?: null,
                            $stdUnitTh ?: null,
                            $barcode ?: null,
                            $tmtGpu ?: null,
                            $brand ?: null,
                            $model ?: null,
                            $dosageForm ?: null,
                            $strength ?: null,
                            $dosageFormId,
                            $edListId,
                            $edSubList ?: null,
                            $productCatId,
                            $productCat ?: null,
                            $minStock,
                            $maxStock,
                            $reorderPoint,
                            $stdPrice,
                            $lastPrice,
                            $aveRate,
                            $active,
                            $lotControl,
                            $expiryControl,
                            0, // serial_control
                            $isGpo ?? 0,
                            $isOriginal ?? 0,
                            $sendMoph,
                            $sendDos ?? 0,
                            $isFreeDispense ?? 0,
                            $mophStatusNote ?: null,
                            $mophUpdatedText ?: null
                        ]);
                        $insertedCount++;
                    }
                }

                $pdo2->commit();

                recordInvActivityLog($pdo2, [
                    'warehouse_id' => $warehouse_id,
                    'page_code' => 'settings',
                    'page_name' => 'ตั้งค่าระบบ (Master Data)',
                    'action' => 'BATCH_IMPORT_ITEMS',
                    'target_type' => 'item',
                    'details' => "นำเข้ารายการพัสดุเข้าคลัง #{$warehouse_id} (เพิ่มใหม่ {$insertedCount}, อัปเดต {$updatedCount}, ข้าม {$skippedCount})",
                    'meta' => ['inserted' => $insertedCount, 'updated' => $updatedCount, 'skipped' => $skippedCount],
                ], $pdo3 ?? null);

                echo json_encode([
                    'status' => 'success',
                    'message' => "นำเข้าข้อมูลวัสดุสำเร็จ (เพิ่มใหม่ {$insertedCount} รายการ, อัปเดต {$updatedCount} รายการ, ข้าม {$skippedCount} รายการ)",
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
        
        $warehouse_id = (int)($data['warehouse_id'] ?? 0);
        $category_id = !empty($data['category_id']) ? (int)$data['category_id'] : null;
        $item_type_id = !empty($data['item_type_id']) ? (int)$data['item_type_id'] : null;
        $item_name = trim($data['item_name'] ?? '');
        $unit_id = !empty($data['unit_id']) ? (int)$data['unit_id'] : null;
        $item_code = trim($data['item_code'] ?? '');

        if (!$warehouse_id || empty($item_name) || !$unit_id) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกชื่อวัสดุ, คลังวัสดุ และหน่วยนับให้ครบถ้วน']);
            exit;
        }

        // Auto-generate item code if empty
        if (empty($item_code)) {
            $item_code = getNextItemCode($pdo2, $warehouse_id, $category_id);
        } else {
            // Check duplicate in same warehouse
            $chk = $pdo2->prepare("SELECT id FROM inv_items WHERE warehouse_id = ? AND item_code = ?");
            $chk->execute([$warehouse_id, $item_code]);
            if ($chk->fetch()) {
                echo json_encode(['status' => 'error', 'message' => "รหัสวัสดุนี้ ({$item_code}) มีอยู่ในคลังนี้แล้ว กรุณาใช้รหัสอื่น"]);
                exit;
            }
        }

        $pharma = resolvePharmacyPayload($pdo2, $data);

        $sql = "INSERT INTO inv_items (
            item_code, old_code, std_code_24, item_name, item_name_en, category_id, item_type_id, warehouse_id, 
            unit_id, purchase_unit_id, issue_unit_id, conversion_rate, packing_size, std_unit_en, std_unit_th,
            barcode, tmt_gpu, brand, product_cat, model, dosage_form, strength,
            dosage_form_id, ed_list_id, ed_sub_list, product_cat_id,
            minimum_stock, maximum_stock, reorder_point, standard_price, last_purchase_price, 
            active, lot_control, expiry_control, serial_control,
            is_gpo, is_original, send_moph, send_dos, is_free_dispense, moph_status_note, moph_updated_text
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?
        )";

        $stmt = $pdo2->prepare($sql);
        $stmt->execute([
            $item_code,
            $data['old_code'] ?? null,
            $pharma['std_code_24'] ?: null,
            $item_name,
            $data['item_name_en'] ?? null,
            $category_id,
            $item_type_id,
            $warehouse_id,
            $unit_id,
            !empty($data['purchase_unit_id']) ? (int)$data['purchase_unit_id'] : null,
            !empty($data['issue_unit_id']) ? (int)$data['issue_unit_id'] : null,
            $data['conversion_rate'] ?? 1.0000,
            $pharma['packing_size'],
            $pharma['std_unit_en'] ?: null,
            $pharma['std_unit_th'] ?: null,
            $data['barcode'] ?? null,
            $pharma['tmt_gpu'] ?: null,
            $data['brand'] ?? null,
            $pharma['product_cat'] ?: null,
            $data['model'] ?? null,
            $pharma['dosage_form'] ?: null,
            $pharma['strength'] ?: null,
            $pharma['dosage_form_id'],
            $pharma['ed_list_id'],
            $pharma['ed_sub_list'] ?: null,
            $pharma['product_cat_id'],
            $data['minimum_stock'] ?? 0,
            $data['maximum_stock'] ?? 0,
            $data['reorder_point'] ?? 0,
            $data['standard_price'] ?? 0,
            $data['last_purchase_price'] ?? 0,
            isset($data['active']) ? (int)$data['active'] : 1,
            isset($data['lot_control']) ? (int)$data['lot_control'] : 0,
            isset($data['expiry_control']) ? (int)$data['expiry_control'] : 0,
            isset($data['serial_control']) ? (int)$data['serial_control'] : 0,
            $pharma['is_gpo'],
            $pharma['is_original'],
            $pharma['send_moph'],
            $pharma['send_dos'],
            $pharma['is_free_dispense'],
            $pharma['moph_status_note'] ?: null,
            $pharma['moph_updated_text'] ?: null
        ]);
        
        $newItemId = $pdo2->lastInsertId();
        recordInvActivityLog($pdo2, [
            'warehouse_id' => $warehouse_id,
            'page_code' => 'settings',
            'page_name' => 'ตั้งค่าระบบ (Master Data)',
            'action' => 'CREATE_ITEM',
            'target_type' => 'item',
            'target_id' => (string)$newItemId,
            'details' => "ลงทะเบียนพัสดุใหม่ [{$item_code}] {$item_name}",
        ], $pdo3 ?? null);

        echo json_encode([
            'status' => 'success', 
            'message' => 'ลงทะเบียนวัสดุใหม่เรียบร้อยแล้ว',
            'item_code' => $item_code,
            'id' => $newItemId
        ]);

    } elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true) ?: [];
        $id = (int)($data['id'] ?? 0);
        $warehouse_id = (int)($data['warehouse_id'] ?? 0);
        $category_id = !empty($data['category_id']) ? (int)$data['category_id'] : null;
        $item_type_id = !empty($data['item_type_id']) ? (int)$data['item_type_id'] : null;
        $item_name = trim($data['item_name'] ?? '');
        $unit_id = !empty($data['unit_id']) ? (int)$data['unit_id'] : null;
        $item_code = trim($data['item_code'] ?? '');

        if (!$id || !$warehouse_id || empty($item_name) || empty($item_code) || !$unit_id) {
            echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน (ต้องระบุ ID, รหัสวัสดุ, ชื่อวัสดุ, คลัง และหน่วยนับ)']);
            exit;
        }

        // Check duplicate with another item in same warehouse
        $chk = $pdo2->prepare("SELECT id FROM inv_items WHERE warehouse_id = ? AND item_code = ? AND id != ?");
        $chk->execute([$warehouse_id, $item_code, $id]);
        if ($chk->fetch()) {
            echo json_encode(['status' => 'error', 'message' => "รหัสวัสดุนี้ ({$item_code}) ซ้ำกับรายการอื่นในคลังนี้"]);
            exit;
        }

        $pharma = resolvePharmacyPayload($pdo2, $data);

        $sql = "UPDATE inv_items SET 
            item_code=?, old_code=?, std_code_24=?, item_name=?, item_name_en=?, category_id=?, item_type_id=?, warehouse_id=?, 
            unit_id=?, purchase_unit_id=?, issue_unit_id=?, conversion_rate=?, packing_size=?, std_unit_en=?, std_unit_th=?,
            barcode=?, tmt_gpu=?, brand=?, product_cat=?, model=?, dosage_form=?, strength=?,
            dosage_form_id=?, ed_list_id=?, ed_sub_list=?, product_cat_id=?,
            minimum_stock=?, maximum_stock=?, reorder_point=?, standard_price=?, last_purchase_price=?, 
            active=?, lot_control=?, expiry_control=?, serial_control=?,
            is_gpo=?, is_original=?, send_moph=?, send_dos=?, is_free_dispense=?, moph_status_note=?, moph_updated_text=?
            WHERE id=?";

        $stmt = $pdo2->prepare($sql);
        $stmt->execute([
            $item_code,
            $data['old_code'] ?? null,
            $pharma['std_code_24'] ?: null,
            $item_name,
            $data['item_name_en'] ?? null,
            $category_id,
            $item_type_id,
            $warehouse_id,
            $unit_id,
            !empty($data['purchase_unit_id']) ? (int)$data['purchase_unit_id'] : null,
            !empty($data['issue_unit_id']) ? (int)$data['issue_unit_id'] : null,
            $data['conversion_rate'] ?? 1.0000,
            $pharma['packing_size'],
            $pharma['std_unit_en'] ?: null,
            $pharma['std_unit_th'] ?: null,
            $data['barcode'] ?? null,
            $pharma['tmt_gpu'] ?: null,
            $data['brand'] ?? null,
            $pharma['product_cat'] ?: null,
            $data['model'] ?? null,
            $pharma['dosage_form'] ?: null,
            $pharma['strength'] ?: null,
            $pharma['dosage_form_id'],
            $pharma['ed_list_id'],
            $pharma['ed_sub_list'] ?: null,
            $pharma['product_cat_id'],
            $data['minimum_stock'] ?? 0,
            $data['maximum_stock'] ?? 0,
            $data['reorder_point'] ?? 0,
            $data['standard_price'] ?? 0,
            $data['last_purchase_price'] ?? 0,
            isset($data['active']) ? (int)$data['active'] : 1,
            isset($data['lot_control']) ? (int)$data['lot_control'] : 0,
            isset($data['expiry_control']) ? (int)$data['expiry_control'] : 0,
            isset($data['serial_control']) ? (int)$data['serial_control'] : 0,
            $pharma['is_gpo'],
            $pharma['is_original'],
            $pharma['send_moph'],
            $pharma['send_dos'],
            $pharma['is_free_dispense'],
            $pharma['moph_status_note'] ?: null,
            $pharma['moph_updated_text'] ?: null,
            $id
        ]);
        
        recordInvActivityLog($pdo2, [
            'warehouse_id' => $warehouse_id,
            'page_code' => 'settings',
            'page_name' => 'ตั้งค่าระบบ (Master Data)',
            'action' => 'UPDATE_ITEM',
            'target_type' => 'item',
            'target_id' => (string)$id,
            'details' => "แก้ไขข้อมูลพัสดุ [{$item_code}] {$item_name}",
        ], $pdo3 ?? null);

        echo json_encode(['status' => 'success', 'message' => 'แก้ไขข้อมูลวัสดุเรียบร้อยแล้ว']);

    } elseif ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'Missing ID']);
            exit;
        }

        // Check if item has transactions
        $chkTx = $pdo2->prepare("SELECT COUNT(*) FROM inv_stock_transactions WHERE item_id = ?");
        $chkTx->execute([$id]);
        if ((int)$chkTx->fetchColumn() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบรายการนี้ได้ เนื่องจากมีประวัติทำรายการในสต็อกแล้ว (แนะนำให้ปิดการใช้งานแทน)']);
            exit;
        }

        $stItemInfo = $pdo2->prepare("SELECT warehouse_id, item_code, item_name FROM inv_items WHERE id = ?");
        $stItemInfo->execute([$id]);
        $itemRow = $stItemInfo->fetch(PDO::FETCH_ASSOC);

        $del = $pdo2->prepare("DELETE FROM inv_items WHERE id = ?");
        $del->execute([$id]);
        recordInvActivityLog($pdo2, [
            'warehouse_id' => !empty($itemRow['warehouse_id']) ? (int)$itemRow['warehouse_id'] : null,
            'page_code' => 'settings',
            'page_name' => 'ตั้งค่าระบบ (Master Data)',
            'action' => 'DELETE_ITEM',
            'target_type' => 'item',
            'target_id' => (string)$id,
            'details' => "ลบรายการพัสดุ " . ($itemRow ? "[{$itemRow['item_code']}] {$itemRow['item_name']}" : "#{$id}"),
        ], $pdo3 ?? null);
        echo json_encode(['status' => 'success', 'message' => 'ลบรายการวัสดุเรียบร้อยแล้ว']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
