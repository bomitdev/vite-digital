<?php
require_once '../../config.php';
require_once '../../auth_utils.php';

$userData = authOptional(); // Can be optional, but we will filter if needed. Actually just view anytime is fine.
$target_id = $_GET['target_id'] ?? null;

if (!$target_id) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo2->prepare("SELECT * FROM revenue_statements WHERE target_id = ? ORDER BY statement_date ASC, month ASC");
    $stmt->execute([$target_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
