<?php
require_once '../../config.php';

header('Content-Type: application/json');

try {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
    $server_type = isset($_GET['server_type']) ? $_GET['server_type'] : 'all';

    $sql = "SELECT * FROM it_servers WHERE 1=1";
    $params = [];

    // Search filter
    if ($search !== '') {
        $sql .= " AND (server_name LIKE :search OR ip_address LIKE :search OR role LIKE :search OR os LIKE :search OR location LIKE :search)";
        $params[':search'] = "%$search%";
    }

    // Status filter
    if ($status !== 'all') {
        $sql .= " AND status = :status";
        $params[':status'] = $status;
    }

    // Type filter
    if ($server_type !== 'all') {
        $sql .= " AND server_type = :server_type";
        $params[':server_type'] = $server_type;
    }

    $sql .= " ORDER BY server_name ASC";

    $stmt = $pdo2->prepare($sql);
    $stmt->execute($params);
    $servers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $servers
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
