<?php
require_once '../../config.php';

try {
    // First, update Notebook from 0003 to 0004
    // We only update those where name is 'คอมพิวเตอร์โน้ตบุ๊ก (Notebook)' or we just check the original ones
    // Wait, the ones we ALREADY changed to 0003 are "All In One" and their names are "คอมพิวเตอร์ All In One".
    // So to be safe, let's select by name!
    
    // Update Notebook
    $stmt1 = $pdo2->prepare("SELECT id, asset_code, name FROM assets WHERE asset_code LIKE '7440-001-0003%' AND name LIKE '%Notebook%'");
    $stmt1->execute();
    $nb_assets = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    echo "Notebooks to update (0003 -> 0004): " . count($nb_assets) . "\n";
    
    foreach ($nb_assets as $asset) {
        $old_code = $asset['asset_code'];
        $new_code = str_replace('7440-001-0003', '7440-001-0004', $old_code);
        echo "Updating NB ID {$asset['id']}: $old_code -> $new_code\n";
        $pdo2->prepare("UPDATE assets SET asset_code = ? WHERE id = ?")->execute([$new_code, $asset['id']]);
    }

    // Now update All In One
    // Some are already 0003 (the first 5), the rest are still 0002
    $stmt2 = $pdo2->prepare("SELECT id, asset_code, name FROM assets WHERE (asset_code LIKE '7440-001-0002%' OR asset_code LIKE '7440-001-0003%') AND name LIKE '%All In One%'");
    $stmt2->execute();
    $aio_assets = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    echo "All In One to ensure are 0003: " . count($aio_assets) . "\n";

    foreach ($aio_assets as $asset) {
        $old_code = $asset['asset_code'];
        // If it's still 0002, change to 0003
        if (strpos($old_code, '7440-001-0002') === 0) {
            $new_code = str_replace('7440-001-0002', '7440-001-0003', $old_code);
            echo "Updating AIO ID {$asset['id']}: $old_code -> $new_code\n";
            $pdo2->prepare("UPDATE assets SET asset_code = ? WHERE id = ?")->execute([$new_code, $asset['id']]);
        }
    }

    echo "Done.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
