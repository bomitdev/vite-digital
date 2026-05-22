<?php
require_once '../../config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));

if (!$data || !isset($data->id)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameter: id']);
    exit;
}

try {
    // Delete the software. Installations will be cascade deleted based on DB schema.
    $sql = "DELETE FROM sw_software WHERE id = :id";
    $stmt = $pdo2->prepare($sql);
    $stmt->execute([':id' => $data->id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Software deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Software not found or could not be deleted.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
