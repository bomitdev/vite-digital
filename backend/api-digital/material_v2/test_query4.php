<?php
require __DIR__ . '/../../config.php';
$stmt = $pdo3->query('DESCRIBE hr_person');
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    if (strpos($c['Field'], 'PREFIX') !== false || strpos($c['Field'], 'FNAME') !== false || strpos($c['Field'], 'NAME') !== false) {
        echo $c['Field'] . "\n";
    }
}
echo "\nDone.";
