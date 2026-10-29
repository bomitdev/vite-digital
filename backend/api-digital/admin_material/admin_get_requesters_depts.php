<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config.php';

if (!isset($pdo3)) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    // Get requesters from hr_person in hosoffice
    $sqlRequesters = "SELECT p.ID as id, 
                             CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as name, 
                             TRIM(ds.HR_DEPARTMENT_SUB_NAME) as department,
                             CONCAT(IFNULL(pos.HR_POSITION_NAME, ''), IF(hl.HR_LEVEL_NAME IS NOT NULL AND hl.HR_LEVEL_NAME != '', CONCAT(' ', hl.HR_LEVEL_NAME), '')) as position
                      FROM hr_person p
                      LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
                      LEFT JOIN hr_department_sub ds ON p.HR_DEPARTMENT_SUB_ID = ds.HR_DEPARTMENT_SUB_ID
                      LEFT JOIN hr_position pos ON p.HR_POSITION_ID = pos.HR_POSITION_ID
                      LEFT JOIN hr_level hl ON p.HR_LEVEL_ID = hl.HR_LEVEL_ID
                      WHERE p.HR_STATUS_ID = 1 AND p.HR_FNAME IS NOT NULL AND p.HR_FNAME != ''
                      ORDER BY p.HR_FNAME ASC";
    $stmtRequesters = $pdo3->query($sqlRequesters);
    $requesters = $stmtRequesters->fetchAll(PDO::FETCH_ASSOC);

    // Get departments from hr_department_sub in hosoffice
    $sqlDepts = "SELECT DISTINCT TRIM(HR_DEPARTMENT_SUB_NAME) as name
                 FROM hr_department_sub
                 WHERE HR_DEPARTMENT_SUB_NAME IS NOT NULL AND HR_DEPARTMENT_SUB_NAME != ''
                 ORDER BY HR_DEPARTMENT_SUB_NAME ASC";
    $stmtDepts = $pdo3->query($sqlDepts);
    $departments = $stmtDepts->fetchAll(PDO::FETCH_COLUMN);

    // Get positions from hr_position in hosoffice
    $sqlPositions = "SELECT DISTINCT CONCAT(IFNULL(pos.HR_POSITION_NAME, ''), '', IFNULL(hl.HR_LEVEL_NAME, '')) as name
                     FROM hr_person p
                     LEFT JOIN hr_position pos ON pos.HR_POSITION_ID = p.HR_POSITION_ID
                     LEFT JOIN hr_level hl ON hl.HR_LEVEL_ID = p.HR_LEVEL_ID
                     WHERE p.HR_STATUS_ID = 1 AND pos.HR_POSITION_NAME IS NOT NULL AND pos.HR_POSITION_NAME != ''
                     ORDER BY name ASC";
    $stmtPositions = $pdo3->query($sqlPositions);
    $positions = $stmtPositions->fetchAll(PDO::FETCH_COLUMN);

    // Merge departments, requesters, and positions from mt_department_signers ($pdo2)
    if (isset($pdo2)) {
        try {
            $stmtSigners = $pdo2->query("SELECT department_name, requester_name, requester_position FROM mt_department_signers ORDER BY department_name ASC");
            $signers = $stmtSigners->fetchAll(PDO::FETCH_ASSOC);
            foreach ($signers as $s) {
                $dName = trim($s['department_name'] ?? '');
                $rName = trim($s['requester_name'] ?? '');
                $rPos = trim($s['requester_position'] ?? '');

                if (!empty($dName) && !in_array($dName, $departments)) {
                    $departments[] = $dName;
                }

                if (!empty($rName)) {
                    $exists = false;
                    foreach ($requesters as $req) {
                        if (trim($req['name']) === $rName) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $requesters[] = [
                            'id' => null,
                            'name' => $rName,
                            'department' => $dName,
                            'position' => $rPos
                        ];
                    }
                }

                if (!empty($rPos) && !in_array($rPos, $positions)) {
                    $positions[] = $rPos;
                }
            }

            // Also merge any other departments from mt_admin_requests
            $stmtReqDepts = $pdo2->query("SELECT DISTINCT TRIM(department) as name FROM mt_admin_requests WHERE department IS NOT NULL AND department != ''");
            $extraDepts = $stmtReqDepts->fetchAll(PDO::FETCH_COLUMN);
            foreach ($extraDepts as $ed) {
                if (!empty($ed) && !in_array($ed, $departments)) {
                    $departments[] = $ed;
                }
            }
        } catch (Exception $ex) {
            // Ignore
        }
    }

    sort($departments, SORT_LOCALE_STRING);
    sort($positions, SORT_LOCALE_STRING);

    echo json_encode([
        'success' => true,
        'requesters' => $requesters,
        'departments' => $departments,
        'positions' => $positions
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
