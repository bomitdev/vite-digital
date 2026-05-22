<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

$categoryId = isset($_GET['category_id']) ? $_GET['category_id'] : null;
// Support filtering by code as well if needed, but ID is safer
$categoryCode = isset($_GET['category_code']) ? $_GET['category_code'] : null;

try {
    $sql = "SELECT ac.* FROM asset_classes ac";
    $params = [];

    if ($categoryCode) {
        $sql .= " JOIN asset_categories cat ON ac.category_id = cat.id WHERE cat.code = ? ORDER BY ac.code ASC";
        $params[] = $categoryCode;
    } elseif ($categoryId) {
        $sql .= " WHERE category_id = ? ORDER BY code ASC";
        $params[] = $categoryId;
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
