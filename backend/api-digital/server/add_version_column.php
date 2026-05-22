<?php
require_once '../../config.php';

try {
    $sql = "ALTER TABLE it_servers ADD COLUMN version VARCHAR(100) AFTER os;";
    $pdo2->exec($sql);
    echo "Column 'version' added successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
