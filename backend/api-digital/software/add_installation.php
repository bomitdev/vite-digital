<?php
require_once '../../config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));

if (!$data || !isset($data->software_id) || empty($data->machine_name)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    exit;
}

try {
    // Check if we hit the max installation limit
    $stmtCheck = $pdo2->prepare("SELECT max_installations FROM sw_software WHERE id = :id");
    $stmtCheck->execute([':id' => $data->software_id]);
    $sw = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($sw) {
        $max = $sw['max_installations'];
        if ($max !== null) {
            $stmtCount = $pdo2->prepare("SELECT COUNT(*) AS total FROM sw_installations WHERE software_id = :id");
            $stmtCount->execute([':id' => $data->software_id]);
            $current = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];

            if ($current >= $max) {
                echo json_encode(['success' => false, 'message' => 'Maximum installation limit reached.']);
                exit;
            }
        }
    }

    $sql = "INSERT INTO sw_installations (software_id, machine_name, user_name, install_date, notes) 
            VALUES (:software_id, :machine_name, :user_name, :install_date, :notes)";
    $stmt = $pdo2->prepare($sql);

    $stmt->execute([
        ':software_id' => $data->software_id,
        ':machine_name' => $data->machine_name,
        ':user_name' => $data->user_name ?? null,
        ':install_date' => !empty($data->install_date) ? $data->install_date : null,
        ':notes' => $data->notes ?? null
    ]);

    echo json_encode(['success' => true, 'message' => 'Installation record added successfully.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
