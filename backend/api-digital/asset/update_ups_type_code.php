<?php
require_once '../../config.php';

try {
    // Select assets that have the old type code 0001
    $stmt = $pdo2->query("SELECT id, asset_code, name FROM assets WHERE asset_code LIKE '7440-013-0001%'");
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Assets to update: " . count($assets) . "\n";
    
    foreach ($assets as $asset) {
        $old_code = $asset['asset_code'];
        $new_code = str_replace('7440-013-0001', '7440-013-0003', $old_code);
        
        echo "Updating ID {$asset['id']}: $old_code -> $new_code\n";
        $pdo2->prepare("UPDATE assets SET asset_code = ? WHERE id = ?")->execute([$new_code, $asset['id']]);
    }

    echo "Done.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
