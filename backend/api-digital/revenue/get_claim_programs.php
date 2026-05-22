<?php
require_once '../../config.php';

try {
    $stmt = $pdo2->prepare("SELECT * FROM revenue_claim_programs ORDER BY program_name ASC");
    $stmt->execute();
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($programs);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
