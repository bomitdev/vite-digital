<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require '../../config.php';

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? null;
$title = $data['title'] ?? '';
$description = $data['description'] ?? '';
$sql_query = $data['sql_query'] ?? '';
$db_connection = $data['db_connection'] ?? 1;
// You should get created_by from session/token technically, but for now we might skip or pass it.
$created_by = $data['created_by'] ?? 0;

if (empty($title) || empty($sql_query)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Title and Query are required."]);
    exit;
}

try {
    if ($id) {
        // Update
        $sql = "UPDATE report_queries SET title = :title, description = :description, sql_query = :sql_query, db_connection = :db_connection, department_id = :department_id WHERE id = :id";
        $stmt = $pdo2->prepare($sql);
        $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':sql_query' => $sql_query,
            ':db_connection' => $db_connection,
            ':department_id' => $data['department_id'] ?? null,
            ':id' => $id
        ]);
        echo json_encode(["success" => true, "message" => "Report updated successfully."]);
    } else {
        // Insert
        $sql = "INSERT INTO report_queries (title, description, sql_query, db_connection, created_by, department_id) VALUES (:title, :description, :sql_query, :db_connection, :created_by, :department_id)";
        $stmt = $pdo2->prepare($sql);
        $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':sql_query' => $sql_query,
            ':db_connection' => $db_connection,
            ':created_by' => $created_by,
            ':department_id' => $data['department_id'] ?? null
        ]);
        echo json_encode(["success" => true, "message" => "Report created successfully.", "id" => $pdo2->lastInsertId()]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
