<?php
require '../../config.php';
try {
    $stmt = $pdo1->query("SELECT 1 FROM hr_person LIMIT 1");
    echo "DB1 has hr_person\n";
} catch (Exception $e) {
}
try {
    $stmt = $pdo2->query("SELECT 1 FROM hr_person LIMIT 1");
    echo "DB2 has hr_person\n";
} catch (Exception $e) {
}
try {
    $stmt = $pdo3->query("SELECT 1 FROM hr_person LIMIT 1");
    echo "DB3 has hr_person\n";
} catch (Exception $e) {
}
