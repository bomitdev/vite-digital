<?php
require 'config.php';
try {
    $t1 = $pdo1->query("SHOW TABLES WHERE Tables_in_$_ENV[DB1_NAME] LIKE '%time%' OR Tables_in_$_ENV[DB1_NAME] LIKE '%check%' OR Tables_in_$_ENV[DB1_NAME] LIKE '%scan%' OR Tables_in_$_ENV[DB1_NAME] LIKE '%leave%'")->fetchAll(PDO::FETCH_COLUMN);
    $t2 = $pdo2->query("SHOW TABLES WHERE Tables_in_$_ENV[DB2_NAME] LIKE '%time%' OR Tables_in_$_ENV[DB2_NAME] LIKE '%check%' OR Tables_in_$_ENV[DB2_NAME] LIKE '%scan%' OR Tables_in_$_ENV[DB2_NAME] LIKE '%leave%'")->fetchAll(PDO::FETCH_COLUMN);

    echo "DB1 Matches:\n" . print_r($t1, true) . "\n";
    echo "DB2 Matches:\n" . print_r($t2, true) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
