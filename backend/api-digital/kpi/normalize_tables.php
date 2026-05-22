<?php
// Mock REQUEST_METHOD for CLI
if (php_sapi_name() === 'cli') {
    $_SERVER['REQUEST_METHOD'] = 'GET';
}
require __DIR__ . '/../../config.php';

try {
    // 1. Create Tables
    $commands = [
        "CREATE TABLE IF NOT EXISTS kpi_levels (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL
        )",
        "CREATE TABLE IF NOT EXISTS kpi_periodicities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL
        )",
        "CREATE TABLE IF NOT EXISTS kpi_units (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL
        )",
        "CREATE TABLE IF NOT EXISTS kpi_calculation_types (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL
        )"
    ];

    foreach ($commands as $sql) {
        $pdo2->exec($sql);
    }
    echo "Tables created.\n";

    // 2. Seed Data
    $levels = ['กระทรวง', 'เขต', 'จังหวัด', 'โรงพยาบาล', 'อื่นๆ'];
    $stmt = $pdo2->prepare("INSERT IGNORE INTO kpi_levels (name) VALUES (:name)"); // Use simpler check logic if IGNORE not supported properly on all engines, but fine here
    foreach ($levels as $l) {
        // Check exist
        $check = $pdo2->prepare("SELECT id FROM kpi_levels WHERE name = ?");
        $check->execute([$l]);
        if (!$check->fetch()) {
            $stmt->execute([':name' => $l]);
        }
    }

    $periodicities = [
        ['month', 'รายเดือน (Monthly)'],
        ['quarter', 'รายไตรมาส (Quarterly)'],
        ['year', 'รายปีงบประมาณ (Fiscal Year)']
    ];
    $stmt = $pdo2->prepare("INSERT IGNORE INTO kpi_periodicities (code, name) VALUES (:code, :name)");
    foreach ($periodicities as $p) {
        $check = $pdo2->prepare("SELECT id FROM kpi_periodicities WHERE code = ?");
        $check->execute([$p[0]]);
        if (!$check->fetch()) {
            $stmt->execute([':code' => $p[0], ':name' => $p[1]]);
        }
    }

    $units = ['คะแนน', 'ระดับ', 'อัตราต่อแสน', 'เกณฑ์ประเมิน', 'เปอร์เซนต์'];
    $stmt = $pdo2->prepare("INSERT IGNORE INTO kpi_units (name) VALUES (:name)");
    foreach ($units as $u) {
        $check = $pdo2->prepare("SELECT id FROM kpi_units WHERE name = ?");
        $check->execute([$u]);
        if (!$check->fetch()) {
            $stmt->execute([':name' => $u]);
        }
    }

    $calcTypes = [
        ['percentage', 'ร้อยละ (Percentage)'],
        ['rate_100k', 'อัตราต่อแสน (Rate per 100k)'],
        ['multiplication', 'การคูณ (Multiplication)'],
        ['direct', 'ระบุค่าเอง (Direct Entry)']
    ];
    $stmt = $pdo2->prepare("INSERT IGNORE INTO kpi_calculation_types (code, name) VALUES (:code, :name)");
    foreach ($calcTypes as $c) {
        $check = $pdo2->prepare("SELECT id FROM kpi_calculation_types WHERE code = ?");
        $check->execute([$c[0]]);
        if (!$check->fetch()) {
            $stmt->execute([':code' => $c[0], ':name' => $c[1]]);
        }
    }
    echo "Data seeded.\n";

    // 3. Add Columns to kpi_definitions
    // Helper to check column existence
    function columnExists($pdo, $table, $column)
    {
        // For SHOW COLUMNS, table name is concatenated directly.
        // If 'LIKE ?' causes issues, the column name also needs to be concatenated.
        // Escaping the column name is crucial if concatenating it directly.
        // For simplicity and to address the 'near ?' error if it's from the LIKE clause,
        // we'll concatenate the column name directly here, assuming $column is safe.
        // A more robust solution would involve PDO::quote for $column.
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE '$column'"); // Table and column names injected directly (internal use only)
        $stmt->execute(); // No parameters needed if both are concatenated
        return $stmt->fetch() !== false;
    }

    if (!columnExists($pdo2, 'kpi_definitions', 'kpi_level_id')) {
        $pdo2->exec("ALTER TABLE kpi_definitions ADD COLUMN kpi_level_id INT DEFAULT NULL");
        echo "Added kpi_level_id.\n";
    }
    if (!columnExists($pdo2, 'kpi_definitions', 'kpi_periodicity_id')) {
        $pdo2->exec("ALTER TABLE kpi_definitions ADD COLUMN kpi_periodicity_id INT DEFAULT NULL");
        echo "Added kpi_periodicity_id.\n";
    }
    if (!columnExists($pdo2, 'kpi_definitions', 'unit_id')) {
        $pdo2->exec("ALTER TABLE kpi_definitions ADD COLUMN unit_id INT DEFAULT NULL");
        echo "Added unit_id.\n";
    }
    if (!columnExists($pdo2, 'kpi_definitions', 'calculation_type_id')) {
        $pdo2->exec("ALTER TABLE kpi_definitions ADD COLUMN calculation_type_id INT DEFAULT NULL"); // Renamed to ensure clarity
        echo "Added calculation_type_id.\n";
    }

    // 4. Migrate Data
    // We assume the old columns 'kpi_level', 'kpi_periodicity', 'unit', 'calculation_type' hold the string/codes.
    echo "Migrating data...\n";

    // Levels
    $pdo2->exec("UPDATE kpi_definitions k JOIN kpi_levels l ON k.kpi_level = l.name SET k.kpi_level_id = l.id WHERE k.kpi_level_id IS NULL");

    // Periodicity
    $pdo2->exec("UPDATE kpi_definitions k JOIN kpi_periodicities p ON k.kpi_periodicity = p.code SET k.kpi_periodicity_id = p.id WHERE k.kpi_periodicity_id IS NULL");

    // Units
    $pdo2->exec("UPDATE kpi_definitions k JOIN kpi_units u ON k.unit = u.name SET k.unit_id = u.id WHERE k.unit_id IS NULL");

    // Calc Types
    $pdo2->exec("UPDATE kpi_definitions k JOIN kpi_calculation_types c ON k.calculation_type = c.code SET k.calculation_type_id = c.id WHERE k.calculation_type_id IS NULL");

    echo "Migration complete.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
