<?php
require 'config.php';

try {
    $pdo2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create table
    $sql = "CREATE TABLE IF NOT EXISTS document_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_key VARCHAR(50) NOT NULL UNIQUE,
        category_name VARCHAR(100) NOT NULL
    )";
    $pdo2->exec($sql);
    echo "Table document_categories created successfully.\n";

    // Insert default categories
    $defaults = [
        ['document', 'ข่าวประกาศ (News & Announcements)'],
        ['policy', 'นโยบาย (Policy)'],
        ['pdpa', 'PDPA'],
        ['sla', 'SLA'],
        ['handbook', 'คู่มือการใช้งาน (Handbook)'],
        ['certificate', 'ใบประกาศนียบัตร (Certificate)'],
        ['communication', 'ช่องทางการสื่อสาร (Communication)']
    ];

    $stmt = $pdo2->prepare("INSERT IGNORE INTO document_categories (category_key, category_name) VALUES (?, ?)");
    foreach ($defaults as $cat) {
        $stmt->execute([$cat[0], $cat[1]]);
    }
    echo "Default categories inserted.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
