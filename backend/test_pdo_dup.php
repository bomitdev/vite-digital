<?php
require_once 'config.php';

try {
    $stmt = $pdo2->prepare("
        INSERT INTO mt_department_signers (department_name, requester_name, requester_position) 
        VALUES (:dept, :name, :pos) 
        ON DUPLICATE KEY UPDATE requester_name = :name, requester_position = :pos
    ");

    $stmt->execute([
        ':dept' => 'Test',
        ':name' => 'Name',
        ':pos' => 'Pos'
    ]);
    echo "Success\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
