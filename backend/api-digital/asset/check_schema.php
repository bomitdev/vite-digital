<?php
require __DIR__ . '/../../config.php';

try {
    $tables = $pdo2->query("SHOW TABLES LIKE 'asset_classes'")->fetchAll();
    echo "asset_classes table: " . (count($tables) > 0 ? "EXISTS" : "MISSING") . "\n";

    if (count($tables) > 0) {
        $count = $pdo2->query("SELECT count(*) FROM asset_classes")->fetchColumn();
        echo "asset_classes rows: " . $count . "\n";
    }

    $cols = $pdo2->query("SHOW COLUMNS FROM asset_types LIKE 'class_id'")->fetchAll();
    echo "asset_types.class_id: " . (count($cols) > 0 ? "EXISTS" : "MISSING") . "\n";

    $cols2 = $pdo2->query("SHOW COLUMNS FROM asset_types LIKE 'code'")->fetchAll();
    echo "asset_types.code: " . (count($cols2) > 0 ? "EXISTS" : "MISSING") . "\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
