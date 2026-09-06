<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config.php';

try {
    $pdo2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create qi_committees table
    $sql1 = "CREATE TABLE IF NOT EXISTS qi_committees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo2->exec($sql1);

    // Create qi_committee_members table
    $sql2 = "CREATE TABLE IF NOT EXISTS qi_committee_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        committee_id INT NOT NULL,
        officer_name VARCHAR(255) NOT NULL,
        role VARCHAR(100) DEFAULT 'กรรมการ',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (committee_id) REFERENCES qi_committees(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo2->exec($sql2);

    // Insert default teams if qi_committees is empty
    $stmt = $pdo2->query("SELECT COUNT(*) FROM qi_committees");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $default_teams = ['HRD', 'HRM', 'PCT', 'RM', 'PTC', 'ENV', 'IC', 'IM', 'FA'];
        
        $insert_sql = "INSERT INTO qi_committees (name) VALUES (:name)";
        $stmt_insert = $pdo2->prepare($insert_sql);
        
        foreach ($default_teams as $team) {
            $stmt_insert->execute([':name' => $team]);
        }
        
        echo json_encode(["status" => "success", "message" => "Tables created and default teams inserted successfully!"]);
    } else {
        echo json_encode(["status" => "success", "message" => "Tables exist and teams are already populated."]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
