<?php
require_once __DIR__ . '/config.php';

try {
    // Check if column exists
    $checkCol = $pdo2->query("SHOW COLUMNS FROM it_projects LIKE 'quarters'")->rowCount();
    if ($checkCol == 0) {
        $sql = "ALTER TABLE it_projects 
                ADD COLUMN `quarters` VARCHAR(255) DEFAULT NULL AFTER `category_id`";
        $pdo2->exec($sql);
        echo "Added 'quarters' column successfully.";
    } else {
        echo "'quarters' column already exists.";
    }
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage();
}
