<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';

header("Content-Type: application/json; charset=UTF-8");

// ใช้ $pdo2 สำหรับฐานข้อมูล digital
if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

try {
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
        // Query companies with counts of active lots and total transactions
        $sql = "
            SELECT 
                c.id, 
                c.company_name, 
                c.created_at,
                (SELECT COUNT(*) FROM mt_admin_lots l WHERE l.vendor COLLATE utf8mb4_unicode_ci = c.company_name COLLATE utf8mb4_unicode_ci AND l.remaining_qty > 0) AS active_lots_count,
                (SELECT COUNT(*) FROM mt_admin_transactions t WHERE t.reference_dest COLLATE utf8mb4_unicode_ci = c.company_name COLLATE utf8mb4_unicode_ci AND t.action_type = 'IN') AS transactions_count
            FROM mt_admin_companies c
            ORDER BY c.company_name ASC
        ";
        $stmt = $pdo2->query($sql);
        $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $companies]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true) ?: $_POST;
    $id = isset($data['id']) ? intval($data['id']) : 0;
    $company_name = isset($data['company_name']) ? trim($data['company_name']) : '';

    if (empty($company_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Company name is required.']);
        exit;
    }

    try {
        if ($id > 0) {
            // Update
            $chk = $pdo2->prepare("SELECT id FROM mt_admin_companies WHERE company_name = :name AND id != :id");
            $chk->execute([':name' => $company_name, ':id' => $id]);
            if ($chk->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'มีชื่อผู้จำหน่ายนี้ในระบบแล้ว']);
                exit;
            }

            $getOld = $pdo2->prepare("SELECT company_name FROM mt_admin_companies WHERE id = :id");
            $getOld->execute([':id' => $id]);
            $oldName = $getOld->fetchColumn();

            $upd = $pdo2->prepare("UPDATE mt_admin_companies SET company_name = :name WHERE id = :id");
            $upd->execute([':name' => $company_name, ':id' => $id]);

            if ($oldName && $oldName !== $company_name) {
                $pdo2->prepare("UPDATE mt_admin_transactions SET reference_dest = ? WHERE reference_dest = ? AND action_type = 'IN'")->execute([$company_name, $oldName]);
                $pdo2->prepare("UPDATE mt_admin_lots SET vendor = ? WHERE vendor = ?")->execute([$company_name, $oldName]);
            }

            echo json_encode(['status' => 'success', 'message' => 'Company updated successfully']);
        } else {
            // Insert or ignore if duplicate
            $stmt = $pdo2->prepare("INSERT INTO mt_admin_companies (company_name) VALUES (:name) ON DUPLICATE KEY UPDATE company_name=company_name");
            $stmt->execute([':name' => $company_name]);
            
            echo json_encode(['status' => 'success', 'message' => 'Company saved successfully']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
}
