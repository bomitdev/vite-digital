<?php
require __DIR__ . '/config.php';
try {
    $stmt = $pdo2->prepare("ALTER TABLE mt_admin_transactions ADD COLUMN bill_number VARCHAR(100) NULL AFTER note");
    $stmt->execute();
    echo "Column bill_number added successfully.";
} catch (Exception $e) {
    echo $e->getMessage();
}
