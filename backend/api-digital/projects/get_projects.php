<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';

// Secure Auth
$userData = authGuard();

try {
    $sql = "SELECT p.*, c.category_name 
            FROM it_projects p 
            LEFT JOIN it_project_categories c ON p.category_id = c.id 
            ORDER BY p.created_at DESC";
    $stmt = $pdo2->prepare($sql);
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $projects
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database Error: ' . $e->getMessage()
    ]);
}
