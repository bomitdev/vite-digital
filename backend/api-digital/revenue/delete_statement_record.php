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

$id = $_GET['id'] ?? null;
if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing ID.']);
    exit;
}

try {
    $stmt = $pdo2->prepare("DELETE FROM revenue_statements WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['status' => 'success', 'message' => 'Statement deleted.']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
