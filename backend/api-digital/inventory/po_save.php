<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'No data provided.']);
    exit;
}

$pr_id          = !empty($data['pr_id']) ? intval($data['pr_id']) : null;
$po_date        = !empty($data['po_date']) ? $data['po_date'] : date('Y-m-d');
$vendor_id      = !empty($data['vendor_id']) ? intval($data['vendor_id']) : null;
$vendor_code    = trim($data['vendor_code'] ?? '');
$vendor_name    = trim($data['vendor_name'] ?? '');
$buy_method     = trim($data['buy_method'] ?? '13');
$budget_type    = trim($data['budget_type'] ?? '30');
$buy_common     = trim($data['buy_common'] ?? '0');
$price_criteria = trim($data['price_criteria'] ?? 'ราคาครั้งก่อน');
$memo_no        = trim($data['memo_no'] ?? 'อย 0033.301/132');
$memo_date      = !empty($data['memo_date']) ? $data['memo_date'] : $po_date;
$delivery_days  = intval($data['delivery_days'] ?? 30);
$contract_no    = trim($data['contract_no'] ?? '');
$committee_name = trim($data['committee_name'] ?? '');
$purchaser_name = trim($data['purchaser_name'] ?? '');
$expected_date  = !empty($data['expected_date']) ? $data['expected_date'] : null;
$note           = trim($data['note'] ?? '');
$items          = $data['items'] ?? [];

if (empty($purchaser_name)) {
    $purchaser_name = 'เจ้าหน้าที่พัสดุ';
}

if (empty($vendor_name) && empty($vendor_id)) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเลือกผู้จำหน่าย']);
    exit;
}

if (empty($items)) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเพิ่มรายการพัสดุที่ต้องการสั่งซื้อ']);
    exit;
}

try {
    $pdo2->beginTransaction();

    // Generate PO Number: PO-YYYYMM-XXXX
    $ym = date('Ym', strtotime($po_date));
    $stmt = $pdo2->prepare("SELECT po_number FROM inv_purchase_orders WHERE po_number LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute(["PO-{$ym}-%"]);
    $last_po = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($last_po && preg_match('/-(\d+)$/', $last_po['po_number'], $matches)) {
        $last_num = intval($matches[1]);
        $new_num = str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $new_num = '0001';
    }
    $po_number = "PO-{$ym}-{$new_num}";

    // Calculate totals
    $total_amount = 0;
    $total_items = count($items);
    foreach ($items as $item) {
        $qty = floatval($item['qty_ordered'] ?? 0);
        $price = floatval($item['unit_price'] ?? 0);
        $total_amount += ($qty * $price);
    }

    // Insert PO Header
    $sql = "INSERT INTO inv_purchase_orders (
                pr_id, po_number, vendor_id, vendor_code, vendor_name, 
                buy_method, budget_type, buy_common, price_criteria, 
                memo_no, memo_date, delivery_days, contract_no, committee_name, 
                po_date, expected_date, purchaser_name, note, total_amount, total_items, status
            ) VALUES (
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, ?, 'ORDERED'
            )";
    $stmt = $pdo2->prepare($sql);
    $stmt->execute([
        $pr_id, $po_number, $vendor_id, $vendor_code, $vendor_name,
        $buy_method, $budget_type, $buy_common, $price_criteria,
        $memo_no, $memo_date, $delivery_days, $contract_no, $committee_name,
        $po_date, $expected_date, $purchaser_name, $note, $total_amount, $total_items
    ]);
    $po_id = $pdo2->lastInsertId();

    // Insert PO Items
    $itemStmt = $pdo2->prepare("INSERT INTO inv_purchase_order_items (
                                    po_id, item_id, item_code, item_name, unit_name, qty_ordered, unit_price, total_price
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($items as $item) {
        $item_id = intval($item['item_id'] ?? 0);
        $item_code = trim($item['item_code'] ?? '');
        $item_name = trim($item['item_name'] ?? '');
        $unit_name = trim($item['unit_name'] ?? 'หน่วย');
        $qty = floatval($item['qty_ordered'] ?? 1);
        $price = floatval($item['unit_price'] ?? 0);
        $total_price = $qty * $price;

        if ($qty > 0) {
            $itemStmt->execute([
                $po_id, $item_id, $item_code, $item_name, $unit_name, $qty, $price, $total_price
            ]);
        }
    }

    $pdo2->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกใบสั่งซื้อ (PO) เรียบร้อยแล้ว!',
        'po_id' => $po_id,
        'po_number' => $po_number
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
