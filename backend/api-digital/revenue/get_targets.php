<?php
require_once '../../config.php';
require_once '../../auth_utils.php';

$userData = authOptional();
$isAdmin = false;
$fullname = '';
$deptName = '';
$subDeptName = '';
$deptId = null;
$colleagues = [];

if ($userData) {
    try {
        $stmtUser = $pdo3->prepare("
            SELECT 
                p.ID,
                CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as FULLNAME,
                p.HR_FNAME,
                p.HR_LNAME,
                p.HR_DEPARTMENT_ID,
                d.HR_DEPARTMENT_NAME,
                p.HR_DEPARTMENT_SUB_ID,
                hds.HR_DEPARTMENT_SUB_NAME
            FROM hr_person p
            LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
            LEFT JOIN hr_department d ON p.HR_DEPARTMENT_ID = d.HR_DEPARTMENT_ID
            LEFT JOIN hr_department_sub hds ON p.HR_DEPARTMENT_SUB_ID = hds.HR_DEPARTMENT_SUB_ID
            WHERE p.ID = ?
        ");
        $stmtUser->execute([$userData['uid']]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $fullname = trim($user['FULLNAME']);
            $deptName = trim($user['HR_DEPARTMENT_NAME'] ?? '');
            $subDeptName = trim($user['HR_DEPARTMENT_SUB_NAME'] ?? '');
            $deptId = $user['HR_DEPARTMENT_ID'] ?? null;
            if (strpos($deptName, 'สุขภาพดิจิทัล') !== false || strpos($subDeptName, 'สุขภาพดิจิทัล') !== false ||
                strpos($deptName, 'บริหาร') !== false || strpos($subDeptName, 'บริหาร') !== false ||
                strpos($deptName, 'ประกัน') !== false || strpos($subDeptName, 'ประกัน') !== false) {
                $isAdmin = true;
            }

            // If not admin, get colleagues in the same department (กลุ่มงาน)
            if (!$isAdmin && !empty($deptId)) {
                $stmtColleagues = $pdo3->prepare("
                    SELECT 
                        CONCAT(p.HR_FNAME, ' ', p.HR_LNAME) as SHORT_NAME,
                        CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as FULL_NAME
                    FROM hr_person p
                    LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
                    WHERE p.HR_DEPARTMENT_ID = ? AND p.HR_STATUS_ID = '01'
                ");
                $stmtColleagues->execute([$deptId]);
                $colleagues = $stmtColleagues->fetchAll(PDO::FETCH_ASSOC);
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
            $matchConds = ["responsible_person LIKE ?"];
            $matchParams = ["%" . $fullname . "%"];

            if (!empty($user['HR_FNAME']) && !empty($user['HR_LNAME'])) {
                $matchConds[] = "responsible_person LIKE ?";
                $matchParams[] = "%" . trim($user['HR_FNAME'] . ' ' . $user['HR_LNAME']) . "%";
            }

            if (!empty($deptName)) {
                $matchConds[] = "responsible_unit LIKE ?";
                $matchParams[] = "%" . $deptName . "%";
            }
            if (!empty($subDeptName) && $subDeptName !== $deptName) {
                $matchConds[] = "responsible_unit LIKE ?";
                $matchParams[] = "%" . $subDeptName . "%";
            }

            foreach ($colleagues as $c) {
                if (!empty($c['SHORT_NAME'])) {
                    $matchConds[] = "responsible_person LIKE ?";
                    $matchParams[] = "%" . trim($c['SHORT_NAME']) . "%";
                }
            }

            $sql = "SELECT * FROM revenue_targets WHERE (" . implode(" OR ", $matchConds) . ") ORDER BY fiscal_year DESC, revenue_name ASC";
            $stmt = $pdo2->prepare($sql);
            $stmt->execute($matchParams);
        }
    }

    $targets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($targets);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
