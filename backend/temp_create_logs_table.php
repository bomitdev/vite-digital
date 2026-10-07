<?php
require __DIR__ . '/config.php';
try {
    $pdo2->exec("
        CREATE TABLE IF NOT EXISTS mt_admin_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_profile_name VARCHAR(255) NOT NULL,
            action VARCHAR(100) NOT NULL,
            details TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Table mt_admin_logs created successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
