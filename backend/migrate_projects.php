<?php
require_once __DIR__ . '/config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS `it_projects` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `project_name` varchar(255) NOT NULL,
        `description` text,
        `status` varchar(50) DEFAULT 'pending',
        `progress` int(11) DEFAULT 0,
        `start_date` date DEFAULT NULL,
        `target_date` date DEFAULT NULL,
        `completed_date` date DEFAULT NULL,
        `manager_name` varchar(255) DEFAULT NULL,
        `created_at` timestamp DEFAULT current_timestamp(),
        `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo2->exec($sql);
    echo "Table it_projects created successfully.";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
