<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
require_once '../../config.php';

try {
    // 1. Create sw_communication_channels table
    $sql = "
    CREATE TABLE IF NOT EXISTS sw_communication_channels (
        id INT AUTO_INCREMENT PRIMARY KEY,
        channel_name VARCHAR(255) NOT NULL,
        category VARCHAR(100) NOT NULL COMMENT 'Internal, External, Customer Service',
        channel_type VARCHAR(100) NOT NULL COMMENT 'Website, Email, Phone, Social Media, Chat, etc.',
        objective TEXT NULL,
        target_audience VARCHAR(255) NULL,
        contact_detail VARCHAR(255) NULL COMMENT 'URL / Link / Number',
        responsible_person VARCHAR(255) NULL,
        department VARCHAR(255) NULL,
        sla_response_time VARCHAR(255) NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'Active' COMMENT 'Active, Backup, Inactive',
        usage_frequency VARCHAR(100) NULL,
        platform_tool VARCHAR(255) NULL,
        formality_level VARCHAR(100) NULL,
        strengths TEXT NULL,
        limitations TEXT NULL,
        risks TEXT NULL,
        improvement_plan TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo2->exec($sql);
    echo "Table sw_communication_channels created or already exists.\n";
    // 2. Create sw_communication_channel_types table
    $sqlType = "
    CREATE TABLE IF NOT EXISTS sw_communication_channel_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type_name VARCHAR(100) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo2->exec($sqlType);
    echo "Table sw_communication_channel_types created or already exists.\n";

    // 3. Insert default types if table is empty
    $stmt = $pdo2->query("SELECT COUNT(*) FROM sw_communication_channel_types");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $defaultTypes = [
            'Website',
            'Email',
            'Phone',
            'Social Media',
            'Chat',
            'Intranet Portal',
            'Physical Notice Board'
        ];

        $insertQuery = "INSERT INTO sw_communication_channel_types (type_name) VALUES (:type_name)";
        $stmtInsert = $pdo2->prepare($insertQuery);

        foreach ($defaultTypes as $type) {
            $stmtInsert->execute([':type_name' => $type]);
        }
        echo "Default channel types inserted.\n";
    }

    echo "Database schema setup complete.\n";
} catch (PDOException $e) {
    die("Error creating table: " . $e->getMessage() . "\n");
}
