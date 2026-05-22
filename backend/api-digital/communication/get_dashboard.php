<?php
require_once '../../config.php';

header('Content-Type: application/json');

try {
    // Total Channels
    $stmtTotal = $pdo2->query("SELECT COUNT(*) AS total FROM sw_communication_channels");
    $total = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

    // Categories Breakdown
    $stmtCategory = $pdo2->query("
        SELECT category, COUNT(*) as count 
        FROM sw_communication_channels 
        GROUP BY category
    ");
    $categories = $stmtCategory->fetchAll(PDO::FETCH_ASSOC);

    // Map categories for easier frontend use
    $cat_map = [
        'Internal' => 0,
        'External' => 0,
        'Customer Service' => 0
    ];
    foreach ($categories as $row) {
        $cat_map[$row['category']] = (int)$row['count'];
    }

    // Status Breakdown
    $stmtStatus = $pdo2->query("
        SELECT status, COUNT(*) as count 
        FROM sw_communication_channels 
        GROUP BY status
    ");
    $statuses = $stmtStatus->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'total' => (int)$total,
            'categories' => $cat_map,
            'statuses' => $statuses
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
