<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config.php';

try {
    $pdo2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create qi_roles table
    $sql = "CREATE TABLE IF NOT EXISTS qi_roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        sort_order INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo2->exec($sql);

    // Insert default roles if table is empty
    $stmt = $pdo2->query("SELECT COUNT(*) FROM qi_roles");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $default_roles = [
            ['name' => 'ประธาน', 'sort_order' => 1],
            ['name' => 'รองประธาน', 'sort_order' => 2],
            ['name' => 'เลขานุการ', 'sort_order' => 3],
            ['name' => 'ผู้ช่วยเลขานุการ', 'sort_order' => 4],
            ['name' => 'กรรมการ', 'sort_order' => 5],
            ['name' => 'ที่ปรึกษา', 'sort_order' => 6]
        ];
        
        $insert_sql = "INSERT INTO qi_roles (name, sort_order) VALUES (:name, :sort_order)";
        $stmt_insert = $pdo2->prepare($insert_sql);
        
        foreach ($default_roles as $role) {
            $stmt_insert->execute([
                ':name' => $role['name'],
                ':sort_order' => $role['sort_order']
            ]);
        }
        
        echo json_encode(["status" => "success", "message" => "Roles table created and default roles inserted successfully!"]);
    } else {
        echo json_encode(["status" => "success", "message" => "Roles table exists and is already populated."]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
