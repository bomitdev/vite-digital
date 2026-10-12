<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$warehouse_id = isset($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : 0;

if (!$warehouse_id) {
    echo json_encode(['status' => 'error', 'message' => 'Warehouse ID is required.']);
    exit;
}

try {
    $stmt = $pdo2->prepare("SELECT id, user_id, access_level, created_at FROM inv_user_warehouses WHERE warehouse_id = ?");
    $stmt->execute([$warehouse_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If we want to enrich with User Name, we could fetch from HosXP or $pdo3
    if (isset($pdo3) && count($users) > 0) {
        $userIds = array_map(function($u) { return $u['user_id']; }, $users);
        $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
        
        $sql = "SELECT p.ID, CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as FULLNAME
                FROM hr_person p
                LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
                WHERE p.ID IN ($placeholders)";
        $stmt2 = $pdo3->prepare($sql);
        $stmt2->execute($userIds);
        $staffNames = [];
        while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            $staffNames[$row['ID']] = $row['FULLNAME'];
        }

        foreach ($users as &$u) {
            $u['user_name'] = isset($staffNames[$u['user_id']]) ? $staffNames[$u['user_id']] : 'Unknown';
        }
    }

    echo json_encode(['status' => 'success', 'data' => $users]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
