<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $stmt = $pdo2->query("SELECT * FROM inv_units ORDER BY name ASC");
        $units = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'data' => $units]);
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        $name = trim($data['name'] ?? '');

        if (!$name) {
            echo json_encode(['status' => 'error', 'message' => 'Name is required.']);
            exit;
        }

        $stmt = $pdo2->prepare("INSERT INTO inv_units (name) VALUES (?)");
        $stmt->execute([$name]);
        echo json_encode(['status' => 'success', 'message' => 'Unit created successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
