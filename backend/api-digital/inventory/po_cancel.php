<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$id = isset($data['id']) ? intval($data['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
$reason = trim($data['reason'] ?? '');

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ระบุ ID ใบสั่งซื้อไม่ถูกต้อง']);
    exit;
}

try {
    // 1. ตรวจสอบว่ามีใบสั่งซื้อนี้อยู่หรือไม่
    $stmt = $pdo2->prepare("SELECT id, po_number, pr_id, status, note FROM inv_purchase_orders WHERE id = ?");
    $stmt->execute([$id]);
    $po = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$po) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลใบสั่งซื้อนี้ในระบบ']);
        exit;
    }

    if ($po['status'] === 'CANCELLED') {
        echo json_encode(['status' => 'error', 'message' => "ใบสั่งซื้อ {$po['po_number']} ได้ถูกยกเลิกไปแล้ว"]);
        exit;
    }

    // 2. ตรวจสอบว่ามีการรับของเข้าคลังไปแล้วหรือไม่
    $stmtRec = $pdo2->prepare("SELECT COALESCE(SUM(qty_received), 0) as total_received FROM inv_purchase_order_items WHERE po_id = ?");
    $stmtRec->execute([$id]);
    $rec = $stmtRec->fetch(PDO::FETCH_ASSOC);

    if ($rec && floatval($rec['total_received']) > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => "ไม่สามารถยกเลิกได้ เนื่องจากมีการตรวจรับของเข้าคลังไปแล้ว (" . number_format($rec['total_received']) . " รายการ/หน่วย) กรุณายกเลิกการรับของเข้าคลังก่อน"
        ]);
        exit;
    }

    // ตรวจสอบในตาราง inv_receipts ว่ามีเอกสารรับเข้าผูกอยู่หรือไม่
    $stmtReceipt = $pdo2->prepare("SELECT COUNT(*) as cnt FROM inv_receipts WHERE po_id = ?");
    $stmtReceipt->execute([$id]);
    $receiptCount = $stmtReceipt->fetch(PDO::FETCH_ASSOC);
    if ($receiptCount && intval($receiptCount['cnt']) > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => "ไม่สามารถยกเลิกได้ เนื่องจากมีใบรับเข้าคลังอ้างอิงถึงใบสั่งซื้อนี้อยู่"
        ]);
        exit;
    }

    // 3. ดำเนินการยกเลิกใบสั่งซื้อ
    $pdo2->beginTransaction();

    $cancelNote = $po['note'] ?? '';
    $timestamp = date('d/m/Y H:i');
    $cancelAppend = "\n[ยกเลิกเมื่อ: {$timestamp}]" . ($reason ? " เหตุผล: {$reason}" : "");
    $updatedNote = trim($cancelNote . $cancelAppend);

    $stmtUpdate = $pdo2->prepare("UPDATE inv_purchase_orders SET status = 'CANCELLED', note = ?, updated_at = NOW() WHERE id = ?");
    $stmtUpdate->execute([$updatedNote, $id]);

    $pdo2->commit();

    echo json_encode([
        'status' => 'success',
        'message' => "ยกเลิกใบสั่งซื้อเลขที่ {$po['po_number']} เรียบร้อยแล้ว",
        'po_id' => $id
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>
