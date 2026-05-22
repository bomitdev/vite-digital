<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once '../../config.php';

if (!isset($pdo2)) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }

    $sql = "
        SELECT 
            r.*, 
            m.name AS material_name, 
            m.code AS material_code,
            m.unit AS material_unit,
            m.balance AS current_balance
        FROM mt_requests r
        JOIN mt_materials m ON r.material_id = m.id
        WHERE r.id = :id
    ";

    $stmt = $pdo2->prepare($sql);
    $stmt->execute([':id' => $id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($request) {
        if (isset($pdo3) && !empty($request['requester_name'])) {
            $posSql = "
                SELECT 
                    CONCAT(pos.HR_POSITION_NAME, 
                        IF(hl.HR_LEVEL_NAME IS NOT NULL AND hl.HR_LEVEL_NAME != '', CONCAT(' ', hl.HR_LEVEL_NAME), '')
                    ) as position_name,
                     pos.HR_POSITION_NAME as position_name2
                FROM hr_person p
                LEFT JOIN hr_prefix pfx ON p.HR_PREFIX_ID = pfx.HR_PREFIX_ID
                LEFT JOIN hr_position pos ON p.HR_POSITION_ID = pos.HR_POSITION_ID
                LEFT JOIN hr_level hl ON p.HR_LEVEL_ID = hl.HR_LEVEL_ID
                WHERE CONCAT(pfx.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) = :name
                LIMIT 1
            ";
            $posStmt = $pdo3->prepare($posSql);
            $posStmt->execute([':name' => $request['requester_name']]);
            $posData = $posStmt->fetch(PDO::FETCH_ASSOC);
            $request['requester_position'] = $posData ? $posData['position_name'] : '';
            $request['position_name2'] = $posData ? $posData['position_name2'] : '';
        } else {
            $request['requester_position'] = '';
            $request['position_name2'] = '';
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
