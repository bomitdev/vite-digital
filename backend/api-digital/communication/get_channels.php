<?php
require_once '../../config.php';

header('Content-Type: application/json');

try {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $category = isset($_GET['category']) ? $_GET['category'] : 'all';

    $sql = "SELECT * FROM sw_communication_channels WHERE 1=1";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (channel_name LIKE :search OR objective LIKE :search OR department LIKE :search)";
        $params[':search'] = "%$search%";
    }

    if ($category !== 'all') {
        $sql .= " AND category = :category";
        $params[':category'] = $category;
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $pdo2->prepare($sql);
    $stmt->execute($params);
    $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $channels
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
