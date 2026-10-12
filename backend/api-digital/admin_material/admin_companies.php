<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

// ใช้ $pdo2 สำหรับฐานข้อมูล digital
if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

try {
    // สร้างตารางอัตโนมัติถ้ายังไม่มี
    $pdo2->exec("
        CREATE TABLE IF NOT EXISTS mt_admin_companies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_name VARCHAR(255) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
} catch (PDOException $e) {
    // Ignore error
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $stmt = $pdo2->query("SELECT * FROM mt_admin_companies ORDER BY company_name ASC");
        $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $companies]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $company_name = isset($data['company_name']) ? trim($data['company_name']) : '';

    if (empty($company_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Company name is required.']);
        exit;
    }

    try {
        // ใช้ INSERT IGNORE หรือ ON DUPLICATE KEY UPDATE เพื่อป้องกันชื่อซ้ำ
        $stmt = $pdo2->prepare("INSERT INTO mt_admin_companies (company_name) VALUES (:name) ON DUPLICATE KEY UPDATE company_name=company_name");
        $stmt->execute([':name' => $company_name]);
        
        echo json_encode(['status' => 'success', 'message' => 'Company saved successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
}
?>
