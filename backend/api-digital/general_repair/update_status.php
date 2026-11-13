<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';
require_once __DIR__ . '/repair_auth_helper.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid Request Method");
    }

    $userCtx = getGeneralRepairUserContext($pdo2, $pdo3);
    if (!$userCtx['can_manage']) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'ไม่มีสิทธิ์เข้าถึง: อัปเดตสถานะงานซ่อมได้เฉพาะผู้ที่มีรายชื่อเป็นช่างและ Admin เท่านั้น'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $status = trim((string)($_POST['status'] ?? ''));
    $technician_id = isset($_POST['technician_id']) && $_POST['technician_id'] !== '' ? (int)$_POST['technician_id'] : null;
    $technician_name = trim((string)($_POST['technician_name'] ?? ''));
    $technician_comment = trim((string)($_POST['technician_comment'] ?? ''));
    $cancel_reason = trim((string)($_POST['cancel_reason'] ?? ''));

    if ($id <= 0) {
        throw new Exception("ระบุ ID ของรายการแจ้งซ่อมไม่ถูกต้อง");
    }
    if ($status === '') {
        throw new Exception("กรุณาระบุสถานะ");
    }

    // Handle image_after upload
    $image_after = null;
    if (isset($_FILES['image_after']) && $_FILES['image_after']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../uploads/repairs/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo($_FILES['image_after']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed, true)) {
            $fn = 'after_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['image_after']['tmp_name'], $uploadDir . $fn)) {
                $image_after = 'backend/uploads/repairs/' . $fn;
            }
        }
    }

    $sql = "UPDATE general_repair_requests SET status = :status";
    $params = [
        ':status' => $status,
        ':id' => $id,
    ];

    if ($technician_name !== '') {
        $sql .= ", technician_name = :technician_name";
        $params[':technician_name'] = $technician_name;
    }
    if ($technician_id !== null) {
        $sql .= ", technician_id = :technician_id";
        $params[':technician_id'] = $technician_id;
    }
    if ($technician_comment !== '') {
        $sql .= ", technician_comment = :technician_comment";
        $params[':technician_comment'] = $technician_comment;
    }
    if ($cancel_reason !== '') {
        $sql .= ", cancel_reason = :cancel_reason";
        $params[':cancel_reason'] = $cancel_reason;
    }
    if ($image_after !== null) {
        $sql .= ", image_after = :image_after";
        $params[':image_after'] = $image_after;
    }

    if ($status === 'In Progress') {
        $sql .= ", received_at = COALESCE(received_at, NOW())";
    } elseif ($status === 'Completed') {
        $sql .= ", completed_at = COALESCE(completed_at, NOW())";
    }

    $sql .= " WHERE id = :id";
    $stmt = $pdo2->prepare($sql);
    $stmt->execute($params);

    echo json_encode([
        'status' => 'success',
        'message' => 'อัปเดตสถานะเรียบร้อยแล้ว'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

