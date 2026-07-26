<?php
require_once __DIR__ . '/config.php';

try {
    $checkCol = $pdo2->query("SHOW COLUMNS FROM it_projects LIKE 'completed_quantity'")->rowCount();
    if ($checkCol == 0) {
        $sql = "ALTER TABLE it_projects 
                ADD COLUMN `completed_quantity` INT DEFAULT 0 AFTER `quantity`";
        $pdo2->exec($sql);
        echo "Added 'completed_quantity' column successfully.";
    } else {
        echo "'completed_quantity' column already exists.";
    }
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage();
}
