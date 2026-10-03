<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';

// Secure Auth
$userData = authGuard();


if (!isset($pdo2)) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->requester_name) || !isset($data->department)) {
    echo json_encode(['success' => false, 'message' => 'Missing requester info']);
    exit;
}

$items = [];
// Support both new 'items' array and old single 'material_id' / 'quantity' format
if (isset($data->items) && is_array($data->items)) {
    $items = $data->items;
} elseif (isset($data->material_id) && isset($data->quantity)) {
    $items[] = (object) [
        'material_id' => $data->material_id,
        'quantity' => $data->quantity
    ];
}

if (empty($items)) {
    echo json_encode(['success' => false, 'message' => 'No items provided']);
    exit;
}

try {
    // Temporary migration: Add request_no if missing
    $checkColumn = $pdo2->query("SHOW COLUMNS FROM mt_admin_requests LIKE 'request_no'")->fetch();
    if (!$checkColumn) {
        $pdo2->exec("ALTER TABLE mt_admin_requests ADD COLUMN request_no VARCHAR(50) AFTER id");
    }

    $pdo2->beginTransaction();

    $request_no = 'REQ-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));

    $stmtCheck = $pdo2->prepare("SELECT id, balance FROM mt_admin_materials WHERE id = :id");

    $sqlInsert = "INSERT INTO mt_admin_requests (request_date, requester_name, department, material_id, quantity, status, request_no) 
            VALUES (CURDATE(), :requester_name, :department, :material_id, :quantity, 'pending', :request_no)";
    $stmtInsert = $pdo2->prepare($sqlInsert);

    foreach ($items as $item) {
        if (!isset($item->material_id) || !isset($item->quantity)) {
            throw new Exception("Invalid item format missing material_id or quantity");
        }

        // Verify material exists and has enough balance
        $stmtCheck->execute([':id' => $item->material_id]);
        $materialRow = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$materialRow) {
            throw new Exception("Material ID {$item->material_id} not found");
        }

        if ($item->quantity <= 0) {
            throw new Exception("Quantity must be greater than 0");
        }

        if ($item->quantity > $materialRow['balance']) {
            throw new Exception("Insufficient stock balance for material ID {$item->material_id}. Requested: {$item->quantity}, Available: {$materialRow['balance']}");
        }

        $stmtInsert->execute([
            ':requester_name' => $data->requester_name,
            ':department' => $data->department,
            ':material_id' => $item->material_id,
            ':quantity' => $item->quantity,
            ':request_no' => $request_no
        ]);
    }

    $pdo2->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Material request submitted successfully'
    ]);
} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
