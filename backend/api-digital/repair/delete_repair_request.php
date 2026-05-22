<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require __DIR__ . '/../../config.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid Request Method");
    }

    $id = isset($_POST['id']) ? $_POST['id'] : null;

    if (!$id) {
        throw new Exception("ID is required");
    }

    // Prepare DELETE statement
    $stmt = $pdo2->prepare("DELETE FROM computer_repair_requests WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Deleted successfully']);
    } else {
        // It's possible the record existed but nothing changed (unlikely for DELETE unless not found)
        // Or if ID doesn't exist.
        // Let's assume success if no exception, but provide info.
        echo json_encode(['status' => 'success', 'message' => 'Request deleted (or did not exist)']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
