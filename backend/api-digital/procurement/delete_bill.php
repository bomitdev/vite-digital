<?php
header("Content-Type: application/json; charset=UTF-8");
require __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';

$userData = authGuard();

try {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['id'])) {
        throw new Exception("Bill ID is required.");
    }
    
    $id = $data['id'];
    
    // Only allow deletion if status is Draft or Forwarded (maybe?)
    // Let's just delete for now.
    $sql = "DELETE FROM procurement_bills WHERE id = :id";
    $stmt = $pdo2->prepare($sql);
    $stmt->execute([':id' => $id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("Failed to delete bill.");
    }
    
    echo json_encode(['status' => 'success', 'message' => 'Bill deleted successfully.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
