<?php
require __DIR__ . '/../../config.php';
require __DIR__ . '/../../auth_utils.php';

$userData = authGuard();

try {
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    
    $sql = "SELECT * FROM procurement_bills WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $sql .= " AND (bill_number LIKE :search OR vendor_name LIKE :search OR notes LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if (!empty($status)) {
        $sql .= " AND status = :status";
        $params[':status'] = $status;
    }
    
    $sql .= " ORDER BY id DESC";
    
    $stmt = $pdo2->prepare($sql);
    $stmt->execute($params);
    $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $bills]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
