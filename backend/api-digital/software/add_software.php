<?php
require_once '../../config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));

if (!$data || !isset($data->name)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameter: name']);
    exit;
}

try {
    $sql = "INSERT INTO sw_software 
            (software_name, version, developer, license_key, license_type, start_date, expiry_date, max_installations) 
            VALUES (:name, :version, :developer, :key, :type, :start_date, :expiry_date, :max_installations)";

    $stmt = $pdo2->prepare($sql);

    // Convert empty strings to null for dates and max_installations
    $start_date = !empty($data->start_date) ? $data->start_date : null;
    $expiry_date = !empty($data->expiry_date) ? $data->expiry_date : null;
    $max_installations = (isset($data->max_installations) && $data->max_installations !== '') ? $data->max_installations : null;

    $stmt->execute([
        ':name' => $data->name,
        ':version' => $data->version ?? null,
        ':developer' => $data->developer ?? null,
        ':key' => $data->license_key ?? null,
        ':type' => $data->license_type ?? null,
        ':start_date' => $start_date,
        ':expiry_date' => $expiry_date,
        ':max_installations' => $max_installations
    ]);

    echo json_encode(['success' => true, 'message' => 'Software added successfully.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
