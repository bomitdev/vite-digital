<?php
require_once __DIR__ . '/config.php';

try {
    $checkCol = $pdo2->query("SHOW COLUMNS FROM it_projects LIKE 'completed_quarters'")->rowCount();
    if ($checkCol == 0) {
        $sql = "ALTER TABLE it_projects 
                ADD COLUMN `completed_quarters` VARCHAR(255) DEFAULT NULL AFTER `quarters`";
        $pdo2->exec($sql);
        echo "Added 'completed_quarters' column successfully.";
    } else {
        echo "'completed_quarters' column already exists.";
    }
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage();
}
