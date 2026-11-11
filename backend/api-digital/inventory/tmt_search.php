<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$pdo2->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

$method = $_SERVER['REQUEST_METHOD'];

// DispUnit translation helper
function translateDispUnit($dispUnit) {
    $u = mb_strtolower(trim($dispUnit));
    $map = [
        'tablet' => 'เม็ด',
        'tab' => 'เม็ด',
        'capsule' => 'แคปซูล',
        'cap' => 'แคปซูล',
        'capsule, hard' => 'แคปซูล',
        'capsule, soft' => 'แคปซูลนิ่ม',
        'bottle' => 'ขวด',
        'bot' => 'ขวด',
        'vial' => 'ไวอัล (Vial)',
        'ampoule' => 'แอมพูล (Ampoule)',
        'amp' => 'แอมพูล',
        'tube' => 'หลอด',
        'bag' => 'ถุง',
        'sachet' => 'ซอง',
        'box' => 'กล่อง',
        'piece' => 'ชิ้น',
        'prefilled syr' => 'กระบอกยา (Prefilled Syr)',
        'suppository' => 'เม็ดยาเหน็บ',
        'strip' => 'แผง',
        'pack' => 'ห่อ',
        'can' => 'กระป๋อง',
        'jar' => 'กระปุก'
    ];
    return $map[$u] ?? $dispUnit;
}

if ($method === 'GET') {
    $q = trim($_GET['q'] ?? '');
    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 25;
    $action = trim($_GET['action'] ?? 'search');

    if ($action === 'get') {
        $tpucode = trim($_GET['tpucode'] ?? '');
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo2->prepare("SELECT * FROM inv_tmt_drugs WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
        } else {
            $stmt = $pdo2->prepare("SELECT * FROM inv_tmt_drugs WHERE tpucode = ? LIMIT 1");
            $stmt->execute([$tpucode]);
        }
        $drug = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($drug) {
            $drug['disp_unit_th'] = translateDispUnit($drug['disp_unit']);
            echo json_encode(['status' => 'success', 'data' => $drug], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลยา'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if ($action === 'stats') {
        $count = (int)$pdo2->query("SELECT COUNT(*) FROM inv_tmt_drugs")->fetchColumn();
        echo json_encode(['status' => 'success', 'total_drugs' => $count], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (mb_strlen($q) < 1) {
        echo json_encode(['status' => 'success', 'data' => []]);
        exit;
    }

    $isDigits = ctype_digit($q);
    $qPrefix = $q . '%';
    $qWild = '%' . $q . '%';

    if ($isDigits) {
        $sql = "
            SELECT id, tpucode, type, trade_name, fsn, active_ingredient, strength, dosage_form, cont_value, cont_unit, disp_unit, manufacturer,
            CASE 
                WHEN tpucode = ? THEN 1
                WHEN tpucode LIKE ? THEN 2
                ELSE 3
            END AS relevance
            FROM inv_tmt_drugs 
            WHERE tpucode LIKE ? OR trade_name LIKE ?
            ORDER BY relevance ASC, trade_name ASC
            LIMIT " . (int)$limit;
        $stmt = $pdo2->prepare($sql);
        $stmt->execute([$q, $qPrefix, $qWild, $qWild]);
    } else {
        $sql = "
            SELECT id, tpucode, type, trade_name, fsn, active_ingredient, strength, dosage_form, cont_value, cont_unit, disp_unit, manufacturer,
            CASE 
                WHEN trade_name LIKE ? THEN 1
                WHEN active_ingredient LIKE ? THEN 2
                WHEN trade_name LIKE ? THEN 3
                ELSE 4
            END AS relevance
            FROM inv_tmt_drugs 
            WHERE trade_name LIKE ? 
               OR active_ingredient LIKE ? 
               OR fsn LIKE ?
               OR tpucode LIKE ?
            ORDER BY relevance ASC, trade_name ASC
            LIMIT " . (int)$limit;
        $stmt = $pdo2->prepare($sql);
        $stmt->execute([
            $qPrefix,
            $qPrefix,
            $qWild,
            $qWild,
            $qWild,
            $qWild,
            $qPrefix
        ]);
    }

    $drugs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($drugs as &$d) {
        $d['disp_unit_th'] = translateDispUnit($d['disp_unit']);
    }

    echo json_encode([
        'status' => 'success',
        'query' => $q,
        'count' => count($drugs),
        'data' => $drugs
    ], JSON_UNESCAPED_UNICODE);
    exit;

} elseif ($method === 'POST') {
    // Quick Add TMT drug directly into warehouse items
    $data = json_decode(file_get_contents("php://input"), true) ?: $_POST;
    $action = trim($data['action'] ?? '');

    if ($action === 'add_to_inventory') {
        $warehouse_id = (int)($data['warehouse_id'] ?? 2); // Default 2 = [03] คลังยา
        $tpucode = trim($data['tpucode'] ?? '');

        if (empty($tpucode)) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุรหัสยา TPUCODE']);
            exit;
        }

        // Fetch TMT drug details
        $stmt = $pdo2->prepare("SELECT * FROM inv_tmt_drugs WHERE tpucode = ? LIMIT 1");
        $stmt->execute([$tpucode]);
        $drug = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$drug) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลยา TMT ในฐานข้อมูล']);
            exit;
        }

        // Check if already in warehouse
        $stmtCheck = $pdo2->prepare("SELECT id, item_code, item_name FROM inv_items WHERE warehouse_id = ? AND item_code = ? LIMIT 1");
        $stmtCheck->execute([$warehouse_id, $drug['tpucode']]);
        $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            echo json_encode([
                'status' => 'exists',
                'message' => "ยานี้ถูกเพิ่มในคลังแล้ว (รหัส: {$existing['item_code']} - {$existing['item_name']})",
                'item_id' => $existing['id']
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Resolve unit_id
        $unitNameTh = translateDispUnit($drug['disp_unit']);
        $unitNameEn = trim($drug['disp_unit']);
        $unitName = !empty($unitNameTh) ? $unitNameTh : (!empty($unitNameEn) ? $unitNameEn : 'ชิ้น');

        $stmtUnit = $pdo2->prepare("SELECT id FROM inv_units WHERE warehouse_id = ? AND (name = ? OR name = ?)");
        $stmtUnit->execute([$warehouse_id, $unitName, $unitNameEn]);
        $unitId = $stmtUnit->fetchColumn();

        if (!$unitId) {
            // Auto create unit in this warehouse
            $stmtMaxCode = $pdo2->prepare("SELECT code FROM inv_units WHERE warehouse_id = ? AND code LIKE 'U%' ORDER BY id DESC LIMIT 1");
            $stmtMaxCode->execute([$warehouse_id]);
            $lastCode = $stmtMaxCode->fetchColumn() ?: '';
            $nextNum = 1;
            if (preg_match('/U(\d+)/i', $lastCode, $m)) {
                $nextNum = (int)$m[1] + 1;
            }
            $newUnitCode = 'U' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
            $stmtInsertUnit = $pdo2->prepare("INSERT INTO inv_units (warehouse_id, code, name, active) VALUES (?, ?, ?, 1)");
            $stmtInsertUnit->execute([$warehouse_id, $newUnitCode, $unitName]);
            $unitId = (int)$pdo2->lastInsertId();
        }

        // Category / Item type
        $categoryId = isset($data['category_id']) ? (int)$data['category_id'] : null;
        $itemTypeId = isset($data['item_type_id']) ? (int)$data['item_type_id'] : null;

        // Compose item name: TradeName (Strength) or FSN
        $tradeName = trim($drug['trade_name']);
        $strength = trim($drug['strength']);
        $activeIng = trim($drug['active_ingredient']);
        $itemName = !empty($tradeName) ? ($tradeName . ($strength ? " {$strength}" : '')) : trim($drug['fsn']);
        $itemNameEn = !empty($activeIng) ? ($activeIng . ($strength ? " {$strength}" : '')) : '';

        $price = isset($data['standard_price']) ? (float)$data['standard_price'] : 0.00;
        $minStock = isset($data['minimum_stock']) ? (int)$data['minimum_stock'] : 0;
        $maxStock = isset($data['maximum_stock']) ? (int)$data['maximum_stock'] : 0;

        $stmtInsert = $pdo2->prepare("
            INSERT INTO inv_items (
                item_code, item_name, item_name_en, warehouse_id, unit_id, 
                category_id, item_type_id, brand, standard_price, last_purchase_price,
                minimum_stock, maximum_stock, reorder_point, lot_control, expiry_control, active
            ) VALUES (
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, 
                ?, ?, ?, 1, 1, 1
            )
        ");

        $stmtInsert->execute([
            $drug['tpucode'],
            $itemName,
            $itemNameEn,
            $warehouse_id,
            $unitId,
            $categoryId,
            $itemTypeId,
            $drug['manufacturer'],
            $price,
            $price,
            $minStock,
            $maxStock,
            $minStock
        ]);

        $newItemId = (int)$pdo2->lastInsertId();

        echo json_encode([
            'status' => 'success',
            'message' => "ลงทะเบียนยา '{$itemName}' เข้าสู่คลังยาสำเร็จ",
            'item_id' => $newItemId,
            'item_code' => $drug['tpucode'],
            'item_name' => $itemName,
            'unit_name' => $unitName
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
