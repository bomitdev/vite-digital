<?php
require_once '../../config.php';

try {
    // 1. Create table
    $sql = "CREATE TABLE IF NOT EXISTS revenue_claim_programs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        program_name VARCHAR(255) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo2->exec($sql);
    echo "Table 'revenue_claim_programs' created or already exists.\n";

    // 2. Insert default data based on user's image
    $defaultPrograms = ['MOPHIC', 'KTB', '43', 'FDH-E-Claim', 'NPRP'];
    
    $stmt = $pdo2->prepare("INSERT IGNORE INTO revenue_claim_programs (program_name) VALUES (?)");
    foreach ($defaultPrograms as $prog) {
        $stmt->execute([$prog]);
    }
    
    echo "Default data inserted successfully.\n";

} catch (PDOException $e) {
    echo "Error setting up claim programs: " . $e->getMessage() . "\n";
}
