<?php
require_once '../../config.php';
try {
    $stmt = $pdo2->query("SHOW COLUMNS FROM mt_materials");
    $cols1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt2 = $pdo2->query("SHOW COLUMNS FROM mt_transactions");
    $cols2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['materials' => $cols1, 'transactions' => $cols2]);
} catch (Exception $e) {
    echo $e->getMessage();
}
