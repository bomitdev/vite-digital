<?php
require_once '../../config.php';
require_once '../../auth_utils.php';

$userData = authOptional();
$isAdmin = false;
$fullname = '';

if ($userData) {
    try {
        $stmtUser = $pdo3->prepare("
            SELECT 
                CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as FULLNAME,
                hds.HR_DEPARTMENT_SUB_NAME
            FROM hr_person p
            LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
            LEFT JOIN hr_department_sub hds ON p.HR_DEPARTMENT_SUB_ID = hds.HR_DEPARTMENT_SUB_ID
            WHERE p.ID = ?
        ");
        $stmtUser->execute([$userData['uid']]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $fullname = trim($user['FULLNAME']);
            $dept = $user['HR_DEPARTMENT_SUB_NAME'] ?? '';
            if (strpos($dept, 'สุขภาพดิจิทัล') !== false || strpos($dept, 'ประกัน') !== false) {
                $isAdmin = true;
            }
        }
    } catch (PDOException $e) {
        // Fallback to non-admin
    }
}

try {
    if ($isAdmin) {
        $stmt = $pdo2->prepare("SELECT * FROM revenue_targets ORDER BY fiscal_year DESC, revenue_name ASC");
        $stmt->execute();
    } else {
        if (empty($fullname)) {
            echo json_encode([]);
            exit;
        } else {
            $stmt = $pdo2->prepare("SELECT * FROM revenue_targets WHERE responsible_person LIKE ? ORDER BY fiscal_year DESC, revenue_name ASC");
            $stmt->execute(["%" . $fullname . "%"]);
        }
    }

    $targets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($targets);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
