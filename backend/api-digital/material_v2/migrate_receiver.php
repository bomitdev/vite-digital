<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

try {
    $stmt = $pdo2->prepare("
        ALTER TABLE mt_transactions
        ADD COLUMN IF NOT EXISTS receiver_name VARCHAR(150) AFTER user_profile_name
    ");
    $stmt->execute();

    echo json_encode(['status' => 'success', 'message' => 'Added receiver_name column successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
}
