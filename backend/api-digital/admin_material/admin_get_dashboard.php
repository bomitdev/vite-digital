<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

// ใช้ $pdo2 สำหรับ digital
if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

try {
    // 1. สรุปจำนวนรายการวัสดุ (SKU) ทั้งหมด
    $stmtTotalMaterials = $pdo2->query("SELECT COUNT(*) AS count FROM mt_admin_materials");
    $total_sku = $stmtTotalMaterials->fetch()['count'];

    // 2. สรุปรายการวัสดุที่ยอดคงเหลือต่ำกว่าเกณฑ์การแจ้งเตือน
    $stmtLowStock = $pdo2->query("SELECT COUNT(*) AS count FROM mt_admin_materials WHERE balance <= min_alert");
    $low_stock = $stmtLowStock->fetch()['count'];

    // 3. ยอดรวมรับเข้า (เดือนปัจจุบัน)
    $stmtTotalIn = $pdo2->query("
        SELECT COALESCE(SUM(quantity), 0) AS total 
        FROM mt_admin_transactions 
        WHERE action_type = 'IN' 
        AND MONTH(action_date) = MONTH(CURRENT_DATE())
        AND YEAR(action_date) = YEAR(CURRENT_DATE())
    ");
    $total_in_month = $stmtTotalIn->fetch()['total'];

    // 4. ยอดรวมจ่ายออก (เดือนปัจจุบัน)
    $stmtTotalOut = $pdo2->query("
        SELECT COALESCE(SUM(quantity), 0) AS total 
        FROM mt_admin_transactions 
        WHERE action_type = 'OUT' 
        AND MONTH(action_date) = MONTH(CURRENT_DATE())
        AND YEAR(action_date) = YEAR(CURRENT_DATE())
    ");
    $total_out_month = $stmtTotalOut->fetch()['total'];

    // 5. 5 การเคลื่อนไหวล่าสุด
    $stmtRecentTx = $pdo2->query("
        SELECT 
            t.id, 
            m.name AS material_name, 
            t.action_type, 
            t.quantity, 
            t.action_date, 
            t.receiver_name,
            t.reference_dest
        FROM mt_admin_transactions t
        LEFT JOIN mt_admin_materials m ON t.material_id = m.id
        ORDER BY t.action_date DESC, t.id DESC
        LIMIT 5
    ");
    $recent_transactions = $stmtRecentTx->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'total_sku' => $total_sku,
            'low_stock_count' => $low_stock,
            'total_in_month' => $total_in_month,
            'total_out_month' => $total_out_month,
            'recent_transactions' => $recent_transactions
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'DB Error: ' . $e->getMessage()
    ]);
}
