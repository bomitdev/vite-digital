<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

$classId = isset($_GET['class_id']) ? $_GET['class_id'] : null;
$classCode = isset($_GET['class_code']) ? $_GET['class_code'] : null;

try {
    $sql = "SELECT t.* FROM asset_types t";
    $params = [];

    if ($classCode) {
        $sql .= " JOIN asset_classes c ON t.class_id = c.class_id WHERE c.code = ? ORDER BY t.code ASC";
        $params[] = $classCode;
    } elseif ($classId) {
        $sql .= " WHERE class_id = ? ORDER BY code ASC";
        $params[] = $classId;
    } else {
        $sql .= " ORDER BY code ASC";
    }

    $stmt = $pdo2->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $data]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
