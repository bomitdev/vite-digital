<?php
require_once '../../config.php';

try {
    $pdo2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "
    CREATE TABLE IF NOT EXISTS inv_user_warehouses (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(50) NOT NULL,
        warehouse_id INT(11) NOT NULL,
        access_level VARCHAR(20) DEFAULT 'STAFF',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_warehouse_idx (user_id, warehouse_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ";
    
    $pdo2->exec($sql);
    echo "Table inv_user_warehouses created successfully.";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
