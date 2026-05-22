<?php
require_once '../../config.php';

$target_id = $_GET['target_id'] ?? null;

if (!$target_id) {
    echo json_encode(['status' => 'error', 'message' => 'Target ID is required.']);
    exit;
}

try {
    $stmt = $pdo2->prepare("SELECT * FROM revenue_results WHERE target_id = ? ORDER BY month ASC");
    $stmt->execute([$target_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
