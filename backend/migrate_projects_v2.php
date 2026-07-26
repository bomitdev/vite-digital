<?php
require_once __DIR__ . '/config.php';

try {
    // 1. Create categories table
    $sql1 = "CREATE TABLE IF NOT EXISTS `it_project_categories` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `category_name` varchar(255) NOT NULL,
        `created_at` timestamp DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo2->exec($sql1);

    // Check if it's empty, then insert default data
    $check = $pdo2->query("SELECT COUNT(*) FROM it_project_categories")->fetchColumn();
    if ($check == 0) {
        $defaults = [
            'หมวดโครงการเฉพาะ',
            'โครงการส่งเสริมสุขภาพ',
            'โครงการโรคไม่ติดต่อ',
            'โครงการโรคติดต่อและภัยสุขภาพ',
            'โครงการซ้อมแผน',
            'โครงการสุขภาพภาคประชาชน',
            'โครงการอาชีวอนามัยและสิ่งแวดล้อม',
            'โครงการเทศบาล (50,000 บาท) ปี 69',
            'โครงการอบต.(65,000 บาท ) ปี 69'
        ];
        
        $stmt = $pdo2->prepare("INSERT INTO it_project_categories (category_name) VALUES (?)");
        foreach ($defaults as $cat) {
            $stmt->execute([$cat]);
        }
    }

    // 2. Add columns to it_projects
    // Check if columns exist
    $checkCol = $pdo2->query("SHOW COLUMNS FROM it_projects LIKE 'quantity'")->rowCount();
    if ($checkCol == 0) {
        $sql2 = "ALTER TABLE it_projects 
                 ADD COLUMN `quantity` INT(11) DEFAULT 0 AFTER `description`,
                 ADD COLUMN `unit_price` DECIMAL(10,2) DEFAULT 0.00 AFTER `quantity`,
                 ADD COLUMN `fiscal_year` VARCHAR(10) DEFAULT NULL AFTER `unit_price`,
                 ADD COLUMN `category_id` INT(11) DEFAULT NULL AFTER `fiscal_year`";
        $pdo2->exec($sql2);
    }
    
    echo "Database updated successfully.";

} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage();
}
