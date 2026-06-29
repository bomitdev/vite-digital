<?php
require_once '../../config.php';

try {
    // Check "All In One" (from 0002 to 0003)
    $stmt1 = $pdo2->query("SELECT id, asset_code, name FROM assets WHERE asset_code LIKE '7440-001-0002%'");
    $aio_assets = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    echo "All In One assets to update (0002 -> 0003): " . count($aio_assets) . "\n";
    
    // Check "Notebook" (from 0003 to 0004)
    $stmt2 = $pdo2->query("SELECT id, asset_code, name FROM assets WHERE asset_code LIKE '7440-001-0003%'");
    $nb_assets = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    echo "Notebook assets to update (0003 -> 0004): " . count($nb_assets) . "\n";
    
    // Update All In One
    foreach ($aio_assets as $asset) {
        $old_code = $asset['asset_code'];
        $new_code = str_replace('7440-001-0002', '7440-001-0003', $old_code);
        echo "Updating AIO ID {$asset['id']}: $old_code -> $new_code\n";
        $pdo2->prepare("UPDATE assets SET asset_code = ? WHERE id = ?")->execute([$new_code, $asset['id']]);
    }

    // Update Notebook
    foreach ($nb_assets as $asset) {
        $old_code = $asset['asset_code'];
        $new_code = str_replace('7440-001-0003', '7440-001-0004', $old_code);
        echo "Updating NB ID {$asset['id']}: $old_code -> $new_code\n";
        $pdo2->prepare("UPDATE assets SET asset_code = ? WHERE id = ?")->execute([$new_code, $asset['id']]);
    }

    echo "Done.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
