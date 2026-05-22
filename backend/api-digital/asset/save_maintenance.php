<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . '/../../config.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['asset_id']) || empty($data['issue'])) {
        throw new Exception("Asset ID and Issue description are required.");
    }

    if (empty($data['id'])) {
        // Insert
        $sql = "INSERT INTO asset_maintenance (asset_id, repair_date, issue, solution, cost, technician, status) 
                VALUES (:aid, :date, :issue, :solution, :cost, :tech, :status)";
    } else {
        // Update
        $sql = "UPDATE asset_maintenance SET 
                repair_date = :date, issue = :issue, solution = :solution, 
                cost = :cost, technician = :tech, status = :status
                WHERE id = :id";
    }

    $stmt = $pdo2->prepare($sql);

    $params = [
        ':date' => !empty($data['repair_date']) ? $data['repair_date'] : date('Y-m-d'),
        ':issue' => $data['issue'],
        ':solution' => $data['solution'] ?? '',
        ':cost' => !empty($data['cost']) ? $data['cost'] : 0,
        ':tech' => $data['technician'] ?? '',
        ':status' => $data['status'] ?? 'Pending'
    ];

    if (empty($data['id'])) {
        $params[':aid'] = $data['asset_id'];
    } else {
        $params[':id'] = $data['id'];
    }

    $stmt->execute($params);

    // Optionally update main asset status if status is 'In Progress' or 'Completed'
    // E.g. if status 'In Progress', set Asset Status to 'Repair'
    if ($data['status'] == 'In Progress' || $data['status'] == 'Pending') {
        $upd = $pdo2->prepare("UPDATE assets SET status = 'Repair' WHERE id = ?");
        $upd->execute([$data['asset_id']]);
    } elseif ($data['status'] == 'Completed') {
        $upd = $pdo2->prepare("UPDATE assets SET status = 'Active' WHERE id = ?");
        $upd->execute([$data['asset_id']]);
    }

    echo json_encode(['status' => 'success', 'message' => 'Maintenance record saved successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
