<?php
require_once '../../config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));

if (!$data || !isset($data->id)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameter: id']);
    exit;
}

try {
    $sql = "UPDATE sw_software SET 
            software_name = :name,
            version = :version,
            developer = :developer,
            license_key = :key,
            license_type = :type,
            start_date = :start_date,
            expiry_date = :expiry_date,
            max_installations = :max_installations
            WHERE id = :id";

    $stmt = $pdo2->prepare($sql);

    $start_date = !empty($data->start_date) ? $data->start_date : null;
    $expiry_date = !empty($data->expiry_date) ? $data->expiry_date : null;
    $max_installations = (isset($data->max_installations) && $data->max_installations !== '') ? $data->max_installations : null;

    $stmt->execute([
        ':id' => $data->id,
        ':name' => $data->name,
        ':version' => $data->version ?? null,
        ':developer' => $data->developer ?? null,
        ':key' => $data->license_key ?? null,
        ':type' => $data->license_type ?? null,
        ':start_date' => $start_date,
        ':expiry_date' => $expiry_date,
        ':max_installations' => $max_installations
    ]);

    echo json_encode(['success' => true, 'message' => 'Software updated successfully.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
