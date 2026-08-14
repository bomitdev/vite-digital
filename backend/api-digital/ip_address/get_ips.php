<?php
require_once '../../config.php';

header('Content-Type: application/json');

try {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : 'all'; // all, active, inactive, reserved
    $vlan = isset($_GET['vlan']) ? $_GET['vlan'] : 'all';

    $sql = "SELECT * FROM it_ip_addresses WHERE 1=1";
    $params = [];

    // Search filter
    if ($search !== '') {
        $sql .= " AND (ip_address LIKE :search OR device_name LIKE :search OR department LIKE :search OR user_name LIKE :search OR mac_address LIKE :search)";
        $params[':search'] = "%$search%";
    }

    // Status filter
    if ($status !== 'all') {
        $sql .= " AND status = :status";
        $params[':status'] = $status;
    }

    // VLAN filter
    if ($vlan !== 'all') {
        $sql .= " AND vlan = :vlan";
        $params[':vlan'] = $vlan;
    }

    // Convert IP to integer for correct sorting
    $sql .= " ORDER BY INET_ATON(ip_address) ASC";

    $stmt = $pdo2->prepare($sql);
    $stmt->execute($params);
    $ips = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $ips
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
