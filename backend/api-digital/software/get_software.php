<?php
require_once '../../config.php';

header('Content-Type: application/json');

try {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : 'all'; // all, active, expiring, expired

    // Subquery to count installations
    $sql = "
        SELECT 
            s.*,
            (SELECT COUNT(*) FROM sw_installations i WHERE i.software_id = s.id) AS current_installations
        FROM sw_software s
        WHERE 1=1
    ";

    $params = [];

    // Search filter
    if ($search !== '') {
        $sql .= " AND (s.software_name LIKE :search OR s.developer LIKE :search OR s.license_key LIKE :search)";
        $params[':search'] = "%$search%";
    }

    // Status filter
    if ($status === 'expiring') {
        // Expiring within 30 days
        $sql .= " AND s.expiry_date IS NOT NULL AND s.expiry_date >= CURDATE() AND s.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
    } elseif ($status === 'expired') {
        $sql .= " AND s.expiry_date IS NOT NULL AND s.expiry_date < CURDATE()";
    } elseif ($status === 'active') {
        $sql .= " AND (s.expiry_date IS NULL OR s.expiry_date >= CURDATE())";
    }

    $sql .= " ORDER BY s.id DESC";

    $stmt = $pdo2->prepare($sql);
    $stmt->execute($params);
    $software = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $software
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
