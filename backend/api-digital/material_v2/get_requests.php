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
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';

    $sql = "
        SELECT 
            r.*, 
            m.name AS material_name, 
            m.code AS material_code,
            m.balance AS current_balance
        FROM mt_requests r
        JOIN mt_materials m ON r.material_id = m.id
        WHERE 1=1
    ";

    $params = [];

    if ($status !== 'all') {
        $sql .= " AND r.status = :status";
        $params[':status'] = $status;
    }

    $sql .= " ORDER BY r.id DESC";

    $stmt = $pdo2->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $ticketsMap = [];

    foreach ($rows as $row) {
        $reqNo = isset($row['request_no']) ? $row['request_no'] : null;
        $groupKey = !empty($reqNo) ? $reqNo : 'LEGACY-' . $row['id'];
        
        if (!isset($ticketsMap[$groupKey])) {
            $ticketsMap[$groupKey] = [
                'request_no' => $reqNo,
                'group_id' => $groupKey,
                'request_date' => $row['request_date'],
                'requester_name' => $row['requester_name'],
                'department' => $row['department'],
                'status' => $row['status'],
                'admin_note' => $row['admin_note'],
                // Set these legacy properties for backwards compatibility in UI until UI is updated
                'id' => $row['id'], 
                'material_name' => $row['material_name'],
                'material_code' => $row['material_code'],
                'quantity' => $row['quantity'],
                'current_balance' => $row['current_balance'],
                'items' => []
            ];
        }

        $ticketsMap[$groupKey]['items'][] = [
            'id' => $row['id'],
            'material_id' => $row['material_id'],
            'material_name' => $row['material_name'],
            'material_code' => $row['material_code'],
            'quantity' => $row['quantity'],
            'status' => $row['status'],
            'admin_note' => $row['admin_note'],
            'current_balance' => $row['current_balance']
        ];
    }

    $tickets = array_values($ticketsMap);

    echo json_encode([
        'success' => true,
        'data' => $tickets
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
