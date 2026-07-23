<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';

header("Content-Type: application/json");

try {
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    $department = isset($_GET['department']) ? trim($_GET['department']) : '';

    // NOTE: it_loans is in $pdo2 (digital_hosoffice database). assets is also in $pdo2.
    $sql = "SELECT l.*, a.asset_code, a.name as asset_name, a.type as asset_type, a.image_path as asset_image_path
FROM it_loans l
LEFT JOIN assets a ON l.asset_id = a.id
WHERE 1=1";

    $params = [];

    if ($status !== '') {
        $sql .= " AND l.status = :status";
        $params[':status'] = $status;
    }

    if ($department !== '') {
        $sql .= " AND l.department = :department";
        $params[':department'] = $department;
    }

    $sql .= " ORDER BY l.created_at DESC";

    $stmt = $pdo2->prepare($sql);
    $stmt->execute($params);
    $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $loans
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'DB Error: ' . $e->getMessage()
    ]);
}
