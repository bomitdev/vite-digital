<?php
require_once __DIR__ . '/config.php';

try {

    // 1. inv_warehouses
    $pdo2->exec("
        CREATE TABLE IF NOT EXISTS inv_warehouses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(10) NOT NULL UNIQUE,
            name VARCHAR(100) NOT NULL,
            active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Seed 20 Warehouses
    $warehouses = [
        ['01', 'คลังยา'],
        ['02', 'วัสดุเภสัชกรรม'],
        ['03', 'วัสดุการแพทย์ทั่วไป'],
        ['04', 'วัสดุวิทยาศาสตร์และการแพทย์'],
        ['05', 'วัสดุเอกซเรย์'],
        ['06', 'วัสดุทันตกรรม'],
        ['07', 'วัสดุบริโภค'],
        ['08', 'วัสดุเครื่องแต่งกาย'],
        ['09', 'วัสดุสำนักงาน'],
        ['10', 'วัสดุยานพาหนะและขนส่ง'],
        ['11', 'วัสดุเชื้อเพลิงและหล่อลื่น'],
        ['12', 'วัสดุไฟฟ้าและวิทยุ'],
        ['13', 'วัสดุโฆษณาและเผยแพร่'],
        ['14', 'วัสดุคอมพิวเตอร์'],
        ['15', 'วัสดุงานบ้านงานครัว'],
        ['16', 'วัสดุก่อสร้าง'],
        ['17', 'วัสดุเกษตร'],
        ['18', 'วัสดุการศึกษา'],
        ['19', 'วัสดุสนาม'],
        ['20', 'วัสดุอื่นๆ']
    ];

    $stmtWh = $pdo2->prepare("INSERT IGNORE INTO inv_warehouses (code, name) VALUES (?, ?)");
    foreach ($warehouses as $wh) {
        $stmtWh->execute($wh);
    }

    // 2. inv_categories
    $pdo2->exec("
        CREATE TABLE IF NOT EXISTS inv_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            warehouse_id INT NOT NULL,
            code VARCHAR(20) NOT NULL,
            name VARCHAR(100) NOT NULL,
            active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (warehouse_id) REFERENCES inv_warehouses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 3. inv_units
    $pdo2->exec("
        CREATE TABLE IF NOT EXISTS inv_units (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Seed some basic units
    $units = ['ชิ้น', 'อัน', 'กล่อง', 'ขวด', 'แพ็ค', 'ม้วน', 'แผง', 'เม็ด', 'แคปซูล', 'หลอด', 'เส้น'];
    $stmtUnit = $pdo2->prepare("INSERT IGNORE INTO inv_units (name) VALUES (?)");
    foreach ($units as $u) {
        $stmtUnit->execute([$u]);
    }

    // 4. inv_vendors
    $pdo2->exec("
        CREATE TABLE IF NOT EXISTS inv_vendors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vendor_code VARCHAR(50) UNIQUE,
            vendor_name VARCHAR(255) NOT NULL,
            tax_id VARCHAR(50),
            address TEXT,
            phone VARCHAR(50),
            email VARCHAR(100),
            contact_person VARCHAR(100),
            bank_info TEXT,
            active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 5. inv_items
    $pdo2->exec("
        CREATE TABLE IF NOT EXISTS inv_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_code VARCHAR(50) NOT NULL UNIQUE,
            old_code VARCHAR(50),
            item_name VARCHAR(255) NOT NULL,
            item_name_en VARCHAR(255),
            category_id INT,
            warehouse_id INT NOT NULL,
            unit_id INT NOT NULL,
            purchase_unit_id INT,
            issue_unit_id INT,
            conversion_rate DECIMAL(10,4) DEFAULT 1.0000,
            barcode VARCHAR(100),
            brand VARCHAR(100),
            model VARCHAR(100),
            minimum_stock INT DEFAULT 0,
            maximum_stock INT DEFAULT 0,
            reorder_point INT DEFAULT 0,
            standard_price DECIMAL(12,2) DEFAULT 0.00,
            last_purchase_price DECIMAL(12,2) DEFAULT 0.00,
            active BOOLEAN DEFAULT TRUE,
            lot_control BOOLEAN DEFAULT FALSE,
            expiry_control BOOLEAN DEFAULT FALSE,
            serial_control BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES inv_categories(id) ON DELETE SET NULL,
            FOREIGN KEY (warehouse_id) REFERENCES inv_warehouses(id) ON DELETE RESTRICT,
            FOREIGN KEY (unit_id) REFERENCES inv_units(id) ON DELETE RESTRICT,
            FOREIGN KEY (purchase_unit_id) REFERENCES inv_units(id) ON DELETE SET NULL,
            FOREIGN KEY (issue_unit_id) REFERENCES inv_units(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo "Phase 1 schema and seed data created successfully.\n";

} catch (PDOException $e) {
    echo "Error creating schema: " . $e->getMessage() . "\n";
}
?>
