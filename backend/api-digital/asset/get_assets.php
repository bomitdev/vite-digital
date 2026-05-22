<?php
require __DIR__ . '/../../config.php';
require __DIR__ . '/../../auth_utils.php';

// Protect this endpoint
$userData = authGuard();

try {
    $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    $os = isset($_GET['os']) ? $_GET['os'] : '';
    $name = isset($_GET['name']) ? $_GET['name'] : '';

    $sql = "SELECT * FROM assets WHERE 1=1";
    $params = [];

    if (!empty($searchTerm)) {
        $sql .= " AND (asset_code LIKE :search OR name LIKE :search OR serial_number LIKE :search OR location LIKE :search OR responsible_person LIKE :search)";
        $params[':search'] = "%$searchTerm%";
    }

    if (!empty($status)) {
        $sql .= " AND status = :status";
        $params[':status'] = $status;
    }

    if (!empty($type)) {
        $sql .= " AND type = :type";
        $params[':type'] = $type;
    }

    if (!empty($name)) {
        $sql .= " AND name = :name";
        $params[':name'] = $name;
    }

    if (!empty($os)) {
        $sql .= " AND os = :os";
        $params[':os'] = $os;
    }

    $year = isset($_GET['year']) ? $_GET['year'] : '';
    if (!empty($year)) {
        // Filter by year in asset_code (e.g. /66%)
        $sql .= " AND asset_code LIKE :year_pattern";
        $params[':year_pattern'] = "%/$year%";
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $pdo2->prepare($sql);
    $stmt->execute($params);
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $assets]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
