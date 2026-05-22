<?php
require __DIR__ . '/../config.php';

try {
    echo "Checking 'category' column in 'pdf_files'...\n";

    // Check if column exists
    $stmt = $pdo2->prepare("SHOW COLUMNS FROM pdf_files LIKE 'category'");
    $stmt->execute();
    $result = $stmt->fetch();

    if (!$result) {
        echo "Column 'category' does not exist. Adding it...\n";
        $alter = $pdo2->prepare("ALTER TABLE pdf_files ADD COLUMN category VARCHAR(50) DEFAULT NULL");
        $alter->execute();
        echo "Column 'category' added successfully.\n";
    } else {
        echo "Column 'category' already exists.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
