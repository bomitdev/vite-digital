<?php
require_once '../../config.php';

header('Content-Type: application/json');

try {
    // 1. Total Software
    $stmtTotal = $pdo2->query("SELECT COUNT(*) AS total FROM sw_software");
    $total_software = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

    // 2. Expiring Soon (<= 30 days) and Expired
    $stmtAlerts = $pdo2->query("
        SELECT 
            SUM(CASE WHEN expiry_date IS NOT NULL AND expiry_date >= CURDATE() AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS expiring_soon,
            SUM(CASE WHEN expiry_date IS NOT NULL AND expiry_date < CURDATE() THEN 1 ELSE 0 END) AS expired
        FROM sw_software
    ");
    $alerts = $stmtAlerts->fetch(PDO::FETCH_ASSOC);

    // 3. Total Installations
    $stmtInstalls = $pdo2->query("SELECT COUNT(*) AS total FROM sw_installations");
    $total_installations = $stmtInstalls->fetch(PDO::FETCH_ASSOC)['total'];

    // 4. Recently Added Installations (limit 5 for dash)
    $stmtRecent = $pdo2->query("
        SELECT i.*, s.software_name 
        FROM sw_installations i
        JOIN sw_software s ON i.software_id = s.id
        ORDER BY i.created_at DESC
        LIMIT 5
    ");
    $recent_installations = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'total_software' => (int)$total_software,
            'expiring_soon' => (int)($alerts['expiring_soon'] ?? 0),
            'expired' => (int)($alerts['expired'] ?? 0),
            'total_installations' => (int)$total_installations,
            'recent_installations' => $recent_installations
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
