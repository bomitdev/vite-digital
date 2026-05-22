<?php
require_once '../../config.php';

try {
    $sql = "ALTER TABLE it_servers ADD COLUMN user_name VARCHAR(150) NULL AFTER role;";
    $pdo2->exec($sql);
    echo "Column 'user_name' added successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
