<?php
require_once '../../config.php';

try {
    // Check asset_classes
    $stmt = $pdo2->query("SELECT * FROM asset_classes WHERE code = '013'");
    $class013 = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$class013) {
        die("Class 013 not found\n");
    }
    $classId = $class013['class_id'];

    // Update asset_types
    $stmt = $pdo2->query("SELECT * FROM asset_types WHERE name LIKE '%สำรอง%'");
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Types found:\n";
    print_r($types);
    
    foreach ($types as $t) {
        if ($t['class_id'] != $classId) {
            echo "Updating type {$t['id']} to class_id $classId\n";
            $pdo2->prepare("UPDATE asset_types SET class_id = ? WHERE id = ?")->execute([$classId, $t['id']]);
        }
    }

    // Update assets
    $stmt = $pdo2->query("SELECT id, asset_code, name FROM assets WHERE asset_code LIKE '7440-003%'");
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Assets to update: " . count($assets) . "\n";
    
    foreach ($assets as $asset) {
        $old_code = $asset['asset_code'];
        $new_code = str_replace('7440-003-', '7440-013-', $old_code);
        
        echo "Updating ID {$asset['id']}: $old_code -> $new_code\n";
        $pdo2->prepare("UPDATE assets SET asset_code = ? WHERE id = ?")->execute([$new_code, $asset['id']]);
    }

    echo "Done.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
