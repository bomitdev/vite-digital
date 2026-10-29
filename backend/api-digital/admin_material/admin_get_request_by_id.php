<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config.php';

if (!isset($pdo2)) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $request_no = isset($_GET['request_no']) ? $_GET['request_no'] : null;

    if ($id <= 0 && !$request_no) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID or Request No']);
        exit;
    }

    // If only ID is provided, find the request_no for this ID
    if ($id > 0 && !$request_no) {
        $stmtNo = $pdo2->prepare("SELECT request_no FROM mt_admin_requests WHERE id = :id");
        $stmtNo->execute([':id' => $id]);
        $rowNo = $stmtNo->fetch(PDO::FETCH_ASSOC);
        $request_no = $rowNo ? $rowNo['request_no'] : null;
    }

    $sql = "
        SELECT 
            r.*, 
            m.name AS material_name, 
            m.code AS material_code,
            m.unit AS material_unit,
            m.balance AS current_balance
        FROM mt_admin_requests r
        JOIN mt_admin_materials m ON r.material_id = m.id
        WHERE " . ($request_no ? "r.request_no = :request_no" : "r.id = :id") . "
    ";

    $stmt = $pdo2->prepare($sql);
    if ($request_no) {
        $stmt->execute([':request_no' => $request_no]);
    } else {
        $stmt->execute([':id' => $id]);
    }
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($items)) {
        $request = $items[0]; // Header info from first item
        $request['items'] = $items;

        $statuses = array_column($items, 'status');
        if (in_array('pending', $statuses)) {
            $request['status'] = 'pending';
        } elseif (in_array('approved', $statuses)) {
            $request['status'] = 'approved';
        } else {
            $request['status'] = 'rejected';
        }

        $savedPosition = !empty($request['requester_position']) ? trim($request['requester_position']) : '';

        if (isset($pdo3) && !empty($request['requester_name'])) {
            $posData = false;
            $cleanName = trim($request['requester_name']);

            // 1. Search by name first
            $posSql = "
                SELECT 
                    p.ID,
                    CONCAT(pos.HR_POSITION_NAME, 
                        IF(hl.HR_LEVEL_NAME IS NOT NULL AND hl.HR_LEVEL_NAME != '', CONCAT(' ', hl.HR_LEVEL_NAME), '')
                    ) as position_name,
                    pos.HR_POSITION_NAME as position_name2,
                    TRIM(ds.HR_DEPARTMENT_SUB_NAME) as hr_department_sub,
                    TRIM(d.HR_DEPARTMENT_NAME) as hr_department
                FROM hr_person p
                LEFT JOIN hr_prefix pfx ON p.HR_PREFIX_ID = pfx.HR_PREFIX_ID
                LEFT JOIN hr_position pos ON p.HR_POSITION_ID = pos.HR_POSITION_ID
                LEFT JOIN hr_level hl ON p.HR_LEVEL_ID = hl.HR_LEVEL_ID
                LEFT JOIN hr_department_sub ds ON p.HR_DEPARTMENT_SUB_ID = ds.HR_DEPARTMENT_SUB_ID
                LEFT JOIN hr_department d ON p.HR_DEPARTMENT_ID = d.HR_DEPARTMENT_ID
                WHERE CONCAT(pfx.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) = :name
                   OR CONCAT(p.HR_FNAME, ' ', p.HR_LNAME) = :name2
                   OR REPLACE(CONCAT(pfx.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME), ' ', '') = REPLACE(:name3, ' ', '')
                LIMIT 1
            ";
            $posStmt = $pdo3->prepare($posSql);
            $posStmt->execute([
                ':name' => $cleanName,
                ':name2' => $cleanName,
                ':name3' => $cleanName
            ]);
            $posData = $posStmt->fetch(PDO::FETCH_ASSOC);

            // 2. If not found by name, check by requester_id if name matches/resembles
            $reqId = !empty($request['requester_id']) ? intval($request['requester_id']) : 0;
            if (!$posData && $reqId > 0) {
                $posSqlId = "
                    SELECT 
                        p.ID,
                        CONCAT(pfx.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as full_name,
                        CONCAT(pos.HR_POSITION_NAME, 
                            IF(hl.HR_LEVEL_NAME IS NOT NULL AND hl.HR_LEVEL_NAME != '', CONCAT(' ', hl.HR_LEVEL_NAME), '')
                        ) as position_name,
                        pos.HR_POSITION_NAME as position_name2,
                        TRIM(ds.HR_DEPARTMENT_SUB_NAME) as hr_department_sub,
                        TRIM(d.HR_DEPARTMENT_NAME) as hr_department
                    FROM hr_person p
                    LEFT JOIN hr_prefix pfx ON p.HR_PREFIX_ID = pfx.HR_PREFIX_ID
                    LEFT JOIN hr_position pos ON p.HR_POSITION_ID = pos.HR_POSITION_ID
                    LEFT JOIN hr_level hl ON p.HR_LEVEL_ID = hl.HR_LEVEL_ID
                    LEFT JOIN hr_department_sub ds ON p.HR_DEPARTMENT_SUB_ID = ds.HR_DEPARTMENT_SUB_ID
                    LEFT JOIN hr_department d ON p.HR_DEPARTMENT_ID = d.HR_DEPARTMENT_ID
                    WHERE p.ID = :id
                    LIMIT 1
                ";
                $posStmtId = $pdo3->prepare($posSqlId);
                $posStmtId->execute([':id' => $reqId]);
                $candidate = $posStmtId->fetch(PDO::FETCH_ASSOC);
                if ($candidate) {
                    $cName = preg_replace('/\s+/', '', $candidate['full_name'] ?? '');
                    $rName = preg_replace('/\s+/', '', $cleanName);
                    if (strpos($cName, $rName) !== false || strpos($rName, $cName) !== false) {
                        $posData = $candidate;
                    }
                }
            }

            $posName = $posData ? ($posData['position_name'] ?? '') : '';
            $posName2 = $posData ? ($posData['position_name2'] ?? '') : '';

            // User-entered position always takes highest precedence
            if (!empty($savedPosition)) {
                $request['requester_position'] = $savedPosition;
                $request['position_name2'] = $savedPosition;
            } else {
                $request['requester_position'] = $posName ?: $posName2;
                $request['position_name2'] = $posName2 ?: $posName;
            }

            $request['hr_department_sub'] = $posData ? ($posData['hr_department_sub'] ?? '') : '';
            $request['hr_department'] = $posData ? ($posData['hr_department'] ?? '') : '';

            // If request department is empty or matches admin default 'กลุ่มงานบริหารทั่วไป' but HR shows a more specific sub-department, use HR sub-department
            if (!empty($request['hr_department_sub']) && (empty($request['department']) || $request['department'] === 'กลุ่มงานบริหารทั่วไป')) {
                $request['department'] = $request['hr_department_sub'];
            }
        } else {
            if (!empty($savedPosition)) {
                $request['requester_position'] = $savedPosition;
                $request['position_name2'] = $savedPosition;
            } else {
                $request['requester_position'] = '';
                $request['position_name2'] = '';
            }
            $request['hr_department_sub'] = '';
            $request['hr_department'] = '';
        }

        echo json_encode([
            'success' => true,
            'data' => $request
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Request not found']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
