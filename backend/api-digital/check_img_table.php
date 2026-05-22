<?php
require '../config.php';

try {
    $stmt = $pdo3->query("DESCRIBE com_repair_img");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($columns, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Error checking com_repair_img: " . $e->getMessage();

    // Try plural
    try {
        $stmt = $pdo3->query("DESCRIBE com_repair_images");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($columns, JSON_PRETTY_PRINT);
    } catch (Exception $e2) {
        echo " | Error checking com_repair_images: " . $e2->getMessage();
    }
}
