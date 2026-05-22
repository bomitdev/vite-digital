<?php
require_once '../../config.php';

try {
    $stmt = $pdo2->prepare("SHOW COLUMNS FROM revenue_results LIKE 'statement_amount'");
    $stmt->execute();
    $columnExists = $stmt->fetch();

    if (!$columnExists) {
        $pdo2->exec("ALTER TABLE revenue_results ADD COLUMN statement_amount DECIMAL(15,2) DEFAULT NULL AFTER collected_amount;");
        echo "Column 'statement_amount' added successfully to 'revenue_results' table.\n";
    } else {
        echo "Column 'statement_amount' already exists.\n";
    }
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
}
