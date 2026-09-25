<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config.php';

try {
    $pdo2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "CREATE TABLE IF NOT EXISTS qi_improvement_plans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        committee_id INT NOT NULL,
        standard TEXT,
        recommendation TEXT,
        plan TEXT,
        period_2568 VARCHAR(255),
        period_2569 VARCHAR(255),
        period_2570 VARCHAR(255),
        period_2571 VARCHAR(255),
        indicator TEXT,
        target TEXT,
        responsible TEXT,
        monitoring_period VARCHAR(255),
        result_2568 VARCHAR(255),
        result_2569 VARCHAR(255),
        result_2570 VARCHAR(255),
        result_2571 VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (committee_id) REFERENCES qi_committees(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo2->exec($sql);
    
    echo json_encode(["status" => "success", "message" => "Table qi_improvement_plans created successfully!"]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
