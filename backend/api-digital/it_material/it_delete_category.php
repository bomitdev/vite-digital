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
    echo json_encode(['status' => 'error', 'message' => 'Invalid category ID']);
    exit;
}

try {
    $stmtCat = $pdo2->prepare("SELECT name FROM mt_it_categories WHERE id = ?");
    $stmtCat->execute([$id]);
    $cat = $stmtCat->fetch(PDO::FETCH_ASSOC);

    if (!$cat) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบหมวดหมู่นี้ในระบบ']);
        exit;
    }

    $force = isset($input['force']) && ($input['force'] === true || $input['force'] === 'true' || $input['force'] === 1 || $input['force'] === '1');

    $chkMat = $pdo2->prepare("SELECT COUNT(*) FROM mt_it_materials WHERE type = ?");
    $chkMat->execute([$cat['name']]);
    $count = intval($chkMat->fetchColumn());

    if ($count > 0 && !$force) {
        echo json_encode([
            'status' => 'has_materials',
            'count' => $count,
            'message' => "ไม่สามารถลบได้ทันที เนื่องจากมีวัสดุจำนวน $count รายการ กำลังใช้งานประเภท \"{$cat['name']}\" อยู่"
        ]);
        exit;
    }

    if ($count > 0 && $force) {
        $updMat = $pdo2->prepare("UPDATE mt_it_materials SET type = '' WHERE type = ?");
        $updMat->execute([$cat['name']]);
    }

    $delStmt = $pdo2->prepare("DELETE FROM mt_it_categories WHERE id = ?");
    $delStmt->execute([$id]);

    $msg = "ลบประเภท \"{$cat['name']}\" เรียบร้อยแล้ว";
    if ($count > 0 && $force) {
        $msg .= " (ปลดประเภทของวัสดุ $count รายการเรียบร้อยแล้ว)";
    }

    echo json_encode([
        'status' => 'success',
        'message' => $msg
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'DB Error: ' . $e->getMessage()
    ]);
}
