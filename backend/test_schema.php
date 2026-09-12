<?php
require __DIR__ . '/config.php';
try {
    $stmt = $pdo2->query("SHOW COLUMNS FROM report_queries");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($columns);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
