<?php
require __DIR__ . '/config.php';
try {
    $stmt = $pdo2->query("SELECT id, user_profile_name, reference_dest FROM mt_admin_transactions WHERE note = 'Initial Balance Migration' LIMIT 5");
    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($res);
} catch (Exception $e) {
    echo $e->getMessage();
}
