<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require __DIR__ . '/../../config.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);
    if (empty($data['name'])) {
        throw new Exception("OS name is required");
    }

    $stmt = $pdo2->prepare("INSERT INTO asset_os (name) VALUES (?)");
    $stmt->execute([$data['name']]);

    echo json_encode(['status' => 'success', 'message' => 'OS added successfully']);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo json_encode(['status' => 'error', 'message' => 'OS already exists']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
