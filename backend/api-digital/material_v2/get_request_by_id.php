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
    $requestNo = isset($_GET['request_no']) ? $_GET['request_no'] : (isset($_GET['id']) ? 'LEGACY-' . $_GET['id'] : '');

    if (empty($requestNo)) {
        echo json_encode(['success' => false, 'message' => 'Invalid Request No']);
        exit;
    }

    $isLegacy = strpos($requestNo, 'LEGACY-') === 0;

    if ($isLegacy) {
        $id = str_replace('LEGACY-', '', $requestNo);
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
    } else {
        $sql = "
            SELECT 
                r.*, 
                m.name AS material_name, 
                m.code AS material_code,
                m.unit AS material_unit,
                m.balance AS current_balance
            FROM mt_requests r
            JOIN mt_materials m ON r.material_id = m.id
            WHERE r.request_no = :request_no
        ";
        $stmt = $pdo2->prepare($sql);
        $stmt->execute([':request_no' => $requestNo]);
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($rows)) {
        $firstRow = $rows[0];
        
        $requestData = [
            'id' => $firstRow['id'],
            'request_no' => isset($firstRow['request_no']) ? $firstRow['request_no'] : null,
            'request_date' => $firstRow['request_date'],
            'requester_name' => $firstRow['requester_name'],
            'department' => $firstRow['department'],
            'status' => $firstRow['status'],
            'items' => []
        ];

        foreach ($rows as $r) {
            $requestData['items'][] = [
                'id' => $r['id'],
                'material_id' => $r['material_id'],
                'material_name' => $r['material_name'],
                'material_code' => $r['material_code'],
                'material_unit' => $r['material_unit'],
                'quantity' => $r['quantity'],
                'current_balance' => $r['current_balance'],
                'status' => $r['status']
            ];
        }

        if (isset($pdo3) && !empty($requestData['requester_name'])) {
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
            $posStmt->execute([':name' => $requestData['requester_name']]);
            $posData = $posStmt->fetch(PDO::FETCH_ASSOC);
            $requestData['requester_position'] = $posData ? $posData['position_name'] : '';
            $requestData['position_name2'] = $posData ? $posData['position_name2'] : '';
        } else {
            $requestData['requester_position'] = '';
            $requestData['position_name2'] = '';
        }

        echo json_encode([
            'success' => true,
            'data' => $requestData
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Request not found']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
