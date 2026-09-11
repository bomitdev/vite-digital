<?php
require_once '../../config.php';
require_once '../../auth_utils.php';

$userData = authOptional();
if (!$userData) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;
$achieved_items = isset($input['achieved_items']) && $input['achieved_items'] !== '' ? $input['achieved_items'] : null;
$collected_amount = isset($input['collected_amount']) && $input['collected_amount'] !== '' ? $input['collected_amount'] : 0.00;
$remark = $input['remark'] ?? null;

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Result ID is required.']);
    exit;
}

// Check user authorization
$isAdmin = false;
$fullname = '';
$shortName = '';

try {
    $stmtUser = $pdo3->prepare("
        SELECT 
            CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as FULLNAME,
            CONCAT(p.HR_FNAME, ' ', p.HR_LNAME) as SHORTNAME,
            d.HR_DEPARTMENT_NAME,
            hds.HR_DEPARTMENT_SUB_NAME
        FROM hr_person p
        LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
        LEFT JOIN hr_department d ON p.HR_DEPARTMENT_ID = d.HR_DEPARTMENT_ID
        LEFT JOIN hr_department_sub hds ON p.HR_DEPARTMENT_SUB_ID = hds.HR_DEPARTMENT_SUB_ID
        WHERE p.ID = ?
    ");
    $stmtUser->execute([$userData['uid']]);
    $uData = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if ($uData) {
        $fullname = trim($uData['FULLNAME']);
        $shortName = trim($uData['SHORTNAME']);
        $deptName = trim($uData['HR_DEPARTMENT_NAME'] ?? '');
        $subDeptName = trim($uData['HR_DEPARTMENT_SUB_NAME'] ?? '');
        if (strpos($deptName, 'สุขภาพดิจิทัล') !== false || strpos($subDeptName, 'สุขภาพดิจิทัล') !== false ||
            strpos($deptName, 'บริหาร') !== false || strpos($subDeptName, 'บริหาร') !== false ||
            strpos($deptName, 'ประกัน') !== false || strpos($subDeptName, 'ประกัน') !== false) {
            $isAdmin = true;
        }
    }
} catch (PDOException $e) {}

if (!$isAdmin) {
    $stmtTarget = $pdo2->prepare("
        SELECT t.responsible_person 
        FROM revenue_results r 
        JOIN revenue_targets t ON r.target_id = t.id 
        WHERE r.id = ?
    ");
    $stmtTarget->execute([$id]);
    $targetRow = $stmtTarget->fetch(PDO::FETCH_ASSOC);
    $resp = $targetRow['responsible_person'] ?? '';
    $canReport = false;
    if (!empty($resp)) {
        if (!empty($fullname) && stripos($resp, $fullname) !== false) {
            $canReport = true;
        } elseif (!empty($shortName) && stripos($resp, $shortName) !== false) {
            $canReport = true;
        }
    }
    if (!$canReport) {
        echo json_encode(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์แก้ไขผลงานรายการนี้ (เฉพาะผู้รับผิดชอบเท่านั้นที่แก้ไขได้)']);
        exit;
    }
}

try {
    $stmt = $pdo2->prepare("UPDATE revenue_results SET achieved_items = ?, collected_amount = ?, remark = ? WHERE id = ?");
    if ($stmt->execute([$achieved_items, $collected_amount, $remark, $id])) {
        echo json_encode(['status' => 'success', 'message' => 'Result updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update result.']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
