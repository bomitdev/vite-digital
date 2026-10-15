<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';
require_once __DIR__ . '/../../auth_utils.php';

header("Content-Type: application/json; charset=UTF-8");

$userData = authGuard();

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true) ?: $_POST;
$id = isset($input['id']) ? intval($input['id']) : 0;

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid company ID']);
    exit;
}

try {
    $stmtComp = $pdo2->prepare("SELECT company_name FROM mt_it_companies WHERE id = ?");
    $stmtComp->execute([$id]);
    $comp = $stmtComp->fetch(PDO::FETCH_ASSOC);

    if (!$comp) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลผู้จำหน่ายนี้ในระบบ']);
        exit;
    }

    $compName = $comp['company_name'];

    // Check if used in remaining lots
    $chkLot = $pdo2->prepare("SELECT COUNT(*) FROM mt_it_lots WHERE vendor COLLATE utf8mb4_unicode_ci = ? AND remaining_qty > 0");
    $chkLot->execute([$compName]);
    $lotCount = $chkLot->fetchColumn();

    if ($lotCount > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => "ไม่สามารถลบได้เนื่องจากมีรายการ Lot ในคลังสินค้า $lotCount รายการ ที่ยังมีของคงเหลือมาจากผู้จำหน่าย \"{$compName}\""
        ]);
        exit;
    }

    $delStmt = $pdo2->prepare("DELETE FROM mt_it_companies WHERE id = ?");
    $delStmt->execute([$id]);

    echo json_encode([
        'status' => 'success',
        'message' => "ลบผู้จำหน่าย \"{$compName}\" เรียบร้อยแล้ว"
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'DB Error: ' . $e->getMessage()
    ]);
}
