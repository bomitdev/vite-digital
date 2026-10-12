<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'No data provided.']);
    exit;
}

$pr_id = intval($data['pr_id']);
$po_date = $data['po_date'];
$purchaser_name = $data['purchaser_name'];
$expected_date = $data['expected_date'] ?? null;
$note = $data['note'] ?? '';
$items = $data['items'] ?? [];

if ($pr_id <= 0 || empty($purchaser_name) || empty($items)) {
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน (ต้องระบุ PR อ้างอิง, ผู้จัดซื้อ, และรายการ)']);
    exit;
}

try {
    $pdo2->beginTransaction();

    // Verify PR is approved
    $stmt = $pdo2->prepare("SELECT status FROM inv_purchase_requests WHERE id = ?");
    $stmt->execute([$pr_id]);
    $pr = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pr || $pr['status'] !== 'DIR_APPROVED') {
        throw new Exception("PR ไม่อยู่ในสถานะอนุมัติ (ไม่สามารถเปิด PO ได้)");
    }

    // Generate PO Number (PO-YYYYMM-XXXX)
    $ym = date('Ym', strtotime($po_date));
    $stmt = $pdo2->prepare("SELECT po_number FROM inv_purchase_orders WHERE po_number LIKE ? ORDER BY po_number DESC LIMIT 1");
    $stmt->execute(["PO-{$ym}-%"]);
    $last_po = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($last_po) {
        $last_num = intval(substr($last_po['po_number'], -4));
        $new_num = str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $new_num = '0001';
    }
    $po_number = "PO-{$ym}-{$new_num}";

    // Insert PO Header
    $stmt = $pdo2->prepare("INSERT INTO inv_purchase_orders (pr_id, po_number, po_date, expected_date, purchaser_name, note, status) VALUES (?, ?, ?, ?, ?, ?, 'ORDERED')");
    $stmt->execute([$pr_id, $po_number, $po_date, $expected_date, $purchaser_name, $note]);
    $po_id = $pdo2->lastInsertId();

    // Insert Items
    $stmt = $pdo2->prepare("INSERT INTO inv_purchase_order_items (po_id, item_id, qty_ordered, unit_price) VALUES (?, ?, ?, ?)");
    foreach ($items as $item) {
        $item_id = intval($item['item_id']);
        $qty = intval($item['qty_ordered']);
        $price = floatval($item['unit_price']);
        if ($item_id > 0 && $qty > 0) {
            $stmt->execute([$po_id, $item_id, $qty, $price]);
        }
    }

    $pdo2->commit();
    echo json_encode(['status' => 'success', 'message' => 'เปิดใบสั่งซื้อ (PO) สำเร็จ!', 'po_id' => $po_id]);

} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
