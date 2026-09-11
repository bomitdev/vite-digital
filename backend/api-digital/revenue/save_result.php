<?php
require_once '../../config.php';
require_once '../../auth_utils.php';

// Verify authentication
$user = authGuard();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['target_id']) || !isset($data['month']) || !isset($data['collected_amount'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    exit;
}

$id = $data['id'] ?? null;
$target_id = $data['target_id'];
$month = $data['month'];
$achieved_items = isset($data['achieved_items']) && $data['achieved_items'] !== '' ? $data['achieved_items'] : null;
$collected_amount = isset($data['collected_amount']) && $data['collected_amount'] !== '' ? $data['collected_amount'] : 0.00;
$report_date = $data['report_date'] ?? date('Y-m-d');
$remark = $data['remark'] ?? '';

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
    $stmtUser->execute([$user['uid']]);
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

// Check target
$stmtTarget = $pdo2->prepare("SELECT id, responsible_person FROM revenue_targets WHERE id = ?");
$stmtTarget->execute([$target_id]);
$targetRow = $stmtTarget->fetch(PDO::FETCH_ASSOC);

if (!$targetRow) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลเป้าหมายรายได้']);
    exit;
}

if (!$isAdmin) {
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
        echo json_encode(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์บันทึกผลงานรายการนี้ (สามารถดูได้อย่างเดียว เฉพาะผู้รับผิดชอบเท่านั้นที่กรอกข้อมูลได้)']);
        exit;
    }
}

try {
    if ($id) {
        $stmt = $pdo2->prepare("UPDATE revenue_results SET 
                month = ?, achieved_items = ?, collected_amount = ?, report_date = ?, remark = ?
            WHERE id = ?");
        $stmt->execute([$month, $achieved_items, $collected_amount, $report_date, $remark, $id]);
    } else {
        // Option check if result for that month exists?
        $check = $pdo2->prepare("SELECT id FROM revenue_results WHERE target_id = ? AND month = ?");
        $check->execute([$target_id, $month]);
        $existing = $check->fetch();

        if ($existing) {
            $stmt = $pdo2->prepare("UPDATE revenue_results SET 
                    achieved_items = ?, collected_amount = ?, report_date = ?, remark = ?
                WHERE id = ?");
            $stmt->execute([$achieved_items, $collected_amount, $report_date, $remark, $existing['id']]);
        } else {
            $stmt = $pdo2->prepare("INSERT INTO revenue_results 
                    (target_id, month, achieved_items, collected_amount, report_date, remark) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$target_id, $month, $achieved_items, $collected_amount, $report_date, $remark]);
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Result saved successfully.']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
