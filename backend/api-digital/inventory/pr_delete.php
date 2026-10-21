<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$id = isset($data['id']) ? intval($data['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ระบุ ID ใบขอซื้อไม่ถูกต้อง']);
    exit;
}

try {
    // 1. ตรวจสอบว่ามีใบขอซื้อนี้อยู่จริงหรือไม่
    $stmt = $pdo2->prepare("SELECT id, pr_number FROM inv_purchase_requests WHERE id = ?");
    $stmt->execute([$id]);
    $pr = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pr) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลใบขอซื้อนี้ในระบบ หรืออาจถูกลบไปแล้ว']);
        exit;
    }

    // 2. ตรวจสอบว่าถูกนำไปสร้าง PO แล้วหรือไม่
    $stmt = $pdo2->prepare("SELECT id, po_number FROM inv_purchase_orders WHERE pr_id = ? LIMIT 1");
    $stmt->execute([$id]);
    $po = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($po) {
        echo json_encode([
            'status' => 'error', 
            'message' => "ไม่สามารถลบใบขอซื้อ {$pr['pr_number']} ได้ เนื่องจากถูกนำไปเปิดใบสั่งซื้อ (PO: {$po['po_number']}) แล้ว"
        ]);
        exit;
    }

    // 3. ดำเนินการลบ
    $pdo2->beginTransaction();

    // ลบรายการพัสดุใน PR
    $stmtItem = $pdo2->prepare("DELETE FROM inv_purchase_request_items WHERE pr_id = ?");
    $stmtItem->execute([$id]);

    // ลบหัวใบขอซื้อ
    $stmtPR = $pdo2->prepare("DELETE FROM inv_purchase_requests WHERE id = ?");
    $stmtPR->execute([$id]);

    $pdo2->commit();

    echo json_encode([
        'status' => 'success', 
        'message' => "ลบใบขอซื้อเลขที่ {$pr['pr_number']} เรียบร้อยแล้ว"
    ]);

} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>
