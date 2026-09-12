<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require '../../config.php';

try {
    // Assuming drugitems table is in HIS database (pdo1)
    // If it's in another DB, change pdo1 to pdo2 or pdo3
    $sql = "SELECT did AS id, name FROM drugitems WHERE istatus = 'Y' ORDER BY name ASC";
    
    // Some hospital databases might not have istatus, so we try a basic query first
    // If istatus throws an error, you can remove WHERE istatus = 'Y'
    $stmt = $pdo1->query($sql); 
    $drugs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($drugs);
} catch (PDOException $e) {
    // Fallback if istatus doesn't exist
    try {
        $sql = "SELECT did AS id, name FROM drugitems ORDER BY name ASC";
        $stmt = $pdo1->query($sql);
        $drugs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($drugs);
    } catch (PDOException $ex) {
        http_response_code(500);
        echo json_encode(["error" => "Database error: " . $ex->getMessage()]);
    }
}
