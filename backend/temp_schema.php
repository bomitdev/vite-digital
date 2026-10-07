<?php
require __DIR__ . '/config.php';
try {
    $stmt = $pdo2->prepare("DESCRIBE mt_admin_transactions");
    $stmt->execute();
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($res);
} catch (Exception $e) {
    echo $e->getMessage();
}
