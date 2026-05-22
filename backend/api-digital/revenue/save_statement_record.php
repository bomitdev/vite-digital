<?php
require_once '../../config.php';
require_once '../../auth_utils.php';

$userData = authOptional();
$isAdmin = false;
if ($userData) {
    try {
        $stmtUser = $pdo3->prepare("
            SELECT hds.HR_DEPARTMENT_SUB_NAME 
            FROM hr_person p
            LEFT JOIN hr_department_sub hds ON p.HR_DEPARTMENT_SUB_ID = hds.HR_DEPARTMENT_SUB_ID
            WHERE p.ID = ?
        ");
        $stmtUser->execute([$userData['uid']]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $dept = $user['HR_DEPARTMENT_SUB_NAME'] ?? '';
            if (strpos(trim($dept), 'สุขภาพดิจิทัล') !== false || strpos(trim($dept), 'บริหาร') !== false || strpos(trim($dept), 'ประกัน') !== false) {
                $isAdmin = true;
            }
        }
    } catch (PDOException $e) {
    }
}

if (!$isAdmin) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Admin only.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['target_id']) || !isset($data['month']) || !isset($data['amount'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    exit;
}

$target_id = $data['target_id'];
$month = $data['month'];
$amount = $data['amount'];
$date = $data['date'] ?? date('Y-m-d');
$remark = $data['remark'] ?? '';
$id = $data['id'] ?? null;

try {
    if ($id) {
        $stmt = $pdo2->prepare("UPDATE revenue_statements SET month=?, statement_amount=?, statement_date=?, remark=? WHERE id=?");
        $stmt->execute([$month, $amount, $date, $remark, $id]);
    } else {
        $stmt = $pdo2->prepare("INSERT INTO revenue_statements (target_id, month, statement_amount, statement_date, remark) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$target_id, $month, $amount, $date, $remark]);
    }
    echo json_encode(['status' => 'success', 'message' => 'Statement saved successfully.']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
