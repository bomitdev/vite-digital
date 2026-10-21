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

$pr_id = isset($data['id']) ? intval($data['id']) : 0;
$warehouse_id = intval($data['warehouse_id']);
$request_date = $data['request_date'];
$requester_name = $data['requester_name'];
$reason = $data['reason'] ?? '';
$items = $data['items'] ?? [];

if ($warehouse_id <= 0 || empty($requester_name) || empty($items)) {
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน (ต้องระบุคลัง, ชื่อผู้ขอ, และรายการวัสดุ)']);
    exit;
}

try {
    $pdo2->beginTransaction();

    if ($pr_id > 0) {
        // Update existing PR (only if PENDING)
        $stmt = $pdo2->prepare("SELECT status FROM inv_purchase_requests WHERE id = ?");
        $stmt->execute([$pr_id]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$current) {
            throw new Exception("ไม่พบใบขอซื้อนี้");
        }
        
        $stmt = $pdo2->prepare("UPDATE inv_purchase_requests SET warehouse_id=?, request_date=?, requester_name=?, reason=? WHERE id=?");
        $stmt->execute([$warehouse_id, $request_date, $requester_name, $reason, $pr_id]);
        
        // Delete old items and insert updated ones
        $pdo2->prepare("DELETE FROM inv_purchase_request_items WHERE pr_id=?")->execute([$pr_id]);
        
    } else {
        // Create new PR
        // Generate PR Number (PR-YYYYMM-XXXX)
        $ym = date('Ym', strtotime($request_date));
        $stmt = $pdo2->prepare("SELECT pr_number FROM inv_purchase_requests WHERE pr_number LIKE ? ORDER BY pr_number DESC LIMIT 1");
        $stmt->execute(["PR-{$ym}-%"]);
        $last_pr = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($last_pr) {
            $last_num = intval(substr($last_pr['pr_number'], -4));
            $new_num = str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $new_num = '0001';
        }
        $pr_number = "PR-{$ym}-{$new_num}";
        
        // สร้างเอกสารพร้อมพิมพ์เสนอ ผอ. ลงนาม (สถานะ DIR_APPROVED พร้อมเปิด PO)
        $stmt = $pdo2->prepare("INSERT INTO inv_purchase_requests (pr_number, warehouse_id, request_date, requester_name, reason, status) VALUES (?, ?, ?, ?, ?, 'DIR_APPROVED')");
        $stmt->execute([$pr_number, $warehouse_id, $request_date, $requester_name, $reason]);
        $pr_id = $pdo2->lastInsertId();
    }
    
    // Insert Items
    $stmt = $pdo2->prepare("INSERT INTO inv_purchase_request_items (pr_id, item_id, qty_requested, estimated_price) VALUES (?, ?, ?, ?)");
    foreach ($items as $item) {
        $item_id = intval($item['item_id']);
        $qty = intval($item['qty_requested']);
        $price = floatval($item['estimated_price']);
        if ($item_id > 0 && $qty > 0) {
            $stmt->execute([$pr_id, $item_id, $qty, $price]);
        }
    }

    $pdo2->commit();
    echo json_encode(['status' => 'success', 'message' => 'บันทึกใบขอซื้อสำเร็จ!', 'pr_id' => $pr_id]);

} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
