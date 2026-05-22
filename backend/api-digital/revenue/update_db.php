<?php
require_once '../../config.php';

try {
    $pdo2->exec("ALTER TABLE revenue_targets ADD COLUMN unit_price VARCHAR(255) DEFAULT NULL AFTER target_amount");
    echo "Added unit_price column.\n";
} catch (PDOException $e) {
    echo "Column unit_price might already exist: " . $e->getMessage() . "\n";
}

try {
    $pdo2->exec("ALTER TABLE revenue_targets ADD COLUMN claim_program VARCHAR(255) DEFAULT NULL AFTER responsible_unit");
    echo "Added claim_program column.\n";
} catch (PDOException $e) {
    echo "Column claim_program might already exist: " . $e->getMessage() . "\n";
}

try {
    // Migrate data from previous import:
    // Some data had "(เป้าหมายต่อหน่วย: XX)" appended to the name.
    // The responsible_unit stored the Claim Program previously.

    // First, copy responsible_unit to claim_program
    $pdo2->exec("UPDATE revenue_targets SET claim_program = responsible_unit, responsible_unit = NULL WHERE claim_program IS NULL");
    echo "Migrated claim_program data.\n";

    // Extract unit_price from revenue_name and clean it
    $stmt = $pdo2->query("SELECT id, revenue_name FROM revenue_targets WHERE revenue_name LIKE '%(เป้าหมายต่อหน่วย:%'");
    $targets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($targets as $t) {
        if (preg_match('/(.*) \(เป้าหมายต่อหน่วย: (.*)\)$/', $t['revenue_name'], $matches)) {
            $name = trim($matches[1]);
            $price = trim($matches[2]);
            $updateStmt = $pdo2->prepare("UPDATE revenue_targets SET revenue_name = ?, unit_price = ? WHERE id = ?");
            $updateStmt->execute([$name, $price, $t['id']]);
        }
    }
    echo "Migrated unit_price data from revenue_name.\n";
} catch (PDOException $e) {
    echo "Error migrating data: " . $e->getMessage() . "\n";
}
