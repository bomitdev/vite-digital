<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $warehouse_id = isset($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : 0;
        $category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
        
        $sql = "SELECT i.*, 
                       w.name as warehouse_name, 
                       c.name as category_name,
                       u.name as unit_name
                FROM inv_items i 
                LEFT JOIN inv_warehouses w ON i.warehouse_id = w.id
                LEFT JOIN inv_categories c ON i.category_id = c.id
                LEFT JOIN inv_units u ON i.unit_id = u.id
                WHERE 1=1 ";
        
        $params = [];

        if ($warehouse_id > 0) {
            $sql .= "AND i.warehouse_id = ? ";
            $params[] = $warehouse_id;
        }
        if ($category_id > 0) {
            $sql .= "AND i.category_id = ? ";
            $params[] = $category_id;
        }
        
        $sql .= "ORDER BY i.item_code ASC";

        $stmt = $pdo2->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'data' => $items]);
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $sql = "INSERT INTO inv_items (
            item_code, old_code, item_name, item_name_en, category_id, warehouse_id, 
            unit_id, purchase_unit_id, issue_unit_id, conversion_rate, barcode, brand, model, 
            minimum_stock, maximum_stock, reorder_point, standard_price, last_purchase_price, 
            active, lot_control, expiry_control, serial_control
        ) VALUES (
            ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?
        )";

        $stmt = $pdo2->prepare($sql);
        $stmt->execute([
            $data['item_code'],
            $data['old_code'] ?? null,
            $data['item_name'],
            $data['item_name_en'] ?? null,
            $data['category_id'] ?: null,
            $data['warehouse_id'],
            $data['unit_id'],
            $data['purchase_unit_id'] ?: null,
            $data['issue_unit_id'] ?: null,
            $data['conversion_rate'] ?? 1.0000,
            $data['barcode'] ?? null,
            $data['brand'] ?? null,
            $data['model'] ?? null,
            $data['minimum_stock'] ?? 0,
            $data['maximum_stock'] ?? 0,
            $data['reorder_point'] ?? 0,
            $data['standard_price'] ?? 0,
            $data['last_purchase_price'] ?? 0,
            isset($data['active']) ? (int)$data['active'] : 1,
            isset($data['lot_control']) ? (int)$data['lot_control'] : 0,
            isset($data['expiry_control']) ? (int)$data['expiry_control'] : 0,
            isset($data['serial_control']) ? (int)$data['serial_control'] : 0
        ]);
        
        echo json_encode(['status' => 'success', 'message' => 'Item created successfully.']);
    } else {
        // PUT and DELETE to be implemented
        echo json_encode(['status' => 'error', 'message' => 'Method not fully implemented yet.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
