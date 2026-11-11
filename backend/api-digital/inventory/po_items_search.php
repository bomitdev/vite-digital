<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$q = trim($_GET['q'] ?? '');
$warehouse_id = isset($_GET['warehouse_id']) && $_GET['warehouse_id'] !== '' ? (int)$_GET['warehouse_id'] : 0;
$limit = intval($_GET['limit'] ?? 50);
if ($limit <= 0) $limit = 50;

$items = [];

try {
    $whOnHandSub = $warehouse_id > 0 
        ? "COALESCE((SELECT SUM(b.balance_qty) FROM inv_stock_balances b WHERE b.item_id = i.id AND b.warehouse_id = " . (int)$warehouse_id . "), 0)"
        : "COALESCE((SELECT SUM(b.balance_qty) FROM inv_stock_balances b WHERE b.item_id = i.id), 0)";

    // Search MySQL inv_items
    $sql = "SELECT 
                i.id,
                i.item_code,
                i.item_name,
                i.item_name_en,
                COALESCE(u.name, 'ชิ้น') as unit_name,
                i.standard_price,
                i.last_purchase_price,
                i.minimum_stock,
                $whOnHandSub as on_hand
            FROM inv_items i
            LEFT JOIN inv_units u ON i.unit_id = u.id
            WHERE i.active = 1";
    
    $params = [];
    if ($warehouse_id > 0) {
        $sql .= " AND i.warehouse_id = ?";
        $params[] = $warehouse_id;
    }
    if (!empty($q)) {
        $sql .= " AND (i.item_code LIKE ? OR i.item_name LIKE ? OR i.item_name_en LIKE ?)";
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    $sql .= " ORDER BY i.item_code ASC LIMIT " . intval($limit);

    $stmt = $pdo2->prepare($sql);
    $stmt->execute($params);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $price = floatval($row['last_purchase_price'] > 0 ? $row['last_purchase_price'] : $row['standard_price']);
        $items[] = [
            'item_id' => intval($row['id']),
            'item_code' => $row['item_code'],
            'item_name' => $row['item_name'],
            'item_name_en' => $row['item_name_en'] ?? '',
            'unit_name' => $row['unit_name'],
            'price' => $price,
            'on_hand' => floatval($row['on_hand']),
            'min_stock' => floatval($row['minimum_stock']),
            'source' => 'LOCAL'
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $items
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
