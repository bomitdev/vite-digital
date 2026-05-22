<?php
require __DIR__ . '/../../config.php';

try {
    $pdo2->beginTransaction();

    // 1. Assets Table
    $sql = "CREATE TABLE IF NOT EXISTS assets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        asset_code VARCHAR(50) NOT NULL UNIQUE COMMENT 'รหัสครุภัณฑ์',
        name VARCHAR(255) NOT NULL,
        type VARCHAR(100), -- PC, Notebook, Printer, etc.
        brand VARCHAR(100),
        model VARCHAR(100),
        serial_number VARCHAR(100),
        spec_cpu VARCHAR(100),
        spec_ram VARCHAR(50),
        spec_storage VARCHAR(100),
        os VARCHAR(100),
        status VARCHAR(50) DEFAULT 'Active', -- Active, Spare, Repair, Write-off, Sold
        purchase_date DATE,
        warranty_expire_date DATE,
        price DECIMAL(10,2),
        location VARCHAR(255),
        responsible_person VARCHAR(255),
        image_path VARCHAR(255),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo2->exec($sql);

    // 2. Asset Software Table
    $sql = "CREATE TABLE IF NOT EXISTS asset_software (
        id INT AUTO_INCREMENT PRIMARY KEY,
        asset_id INT NOT NULL,
        software_name VARCHAR(255) NOT NULL,
        version VARCHAR(50),
        license_key VARCHAR(255),
        license_type VARCHAR(50), -- Perpetual, Subscription, Trial, Free
        install_date DATE,
        expiry_date DATE,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo2->exec($sql);

    // 3. Asset Maintenance / Repair Log
    $sql = "CREATE TABLE IF NOT EXISTS asset_maintenance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        asset_id INT NOT NULL,
        repair_date DATE NOT NULL,
        issue TEXT NOT NULL,
        solution TEXT,
        cost DECIMAL(10,2) DEFAULT 0,
        technician VARCHAR(100), -- Service provider or internal staff
        status VARCHAR(50) DEFAULT 'Pending', -- Pending, In Progress, Completed, Cannot Fix
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo2->exec($sql);

    $pdo2->commit();
    echo "Asset tables created successfully.";
} catch (PDOException $e) {
    $pdo2->rollBack();
    echo "Error: " . $e->getMessage();
}
