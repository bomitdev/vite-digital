<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$warehouse_id = isset($_GET['warehouse_id']) && $_GET['warehouse_id'] !== '' ? (int)$_GET['warehouse_id'] : 0;
$low_items = [];

try {
    $whOnHandSub = $warehouse_id > 0 
        ? "COALESCE((SELECT SUM(b.balance_qty) FROM inv_stock_balances b WHERE b.item_id = i.id AND b.warehouse_id = " . (int)$warehouse_id . "), 0)"
        : "COALESCE((SELECT SUM(b.balance_qty) FROM inv_stock_balances b WHERE b.item_id = i.id), 0)";

    // Search MySQL low stock items
    $sql = "SELECT 
                i.id as item_id,
                i.item_code,
                i.item_name,
                i.item_name_en,
                COALESCE(u.name, 'ชิ้น') as unit_name,
                i.minimum_stock,
                i.reorder_point,
                COALESCE(i.last_purchase_price, i.standard_price) as price,
                $whOnHandSub as on_hand
            FROM inv_items i
            LEFT JOIN inv_units u ON i.unit_id = u.id
            WHERE i.active = 1";

    $params = [];
    if ($warehouse_id > 0) {
        $sql .= " AND i.warehouse_id = ?";
        $params[] = $warehouse_id;
    }
    
    $sql .= " HAVING (on_hand <= minimum_stock OR on_hand = 0)
              ORDER BY on_hand ASC
              LIMIT 50";

    $stmt = $pdo2->prepare($sql);
    $stmt->execute($params);
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $needed = ($r['minimum_stock'] > $r['on_hand']) ? ($r['minimum_stock'] * 2 - $r['on_hand']) : 10;
        if ($needed <= 0) $needed = 1;
        $low_items[] = [
            'item_id' => intval($r['item_id']),
            'item_code' => $r['item_code'],
            'item_name' => $r['item_name'],
            'item_name_en' => $r['item_name_en'] ?? '',
            'unit_name' => $r['unit_name'],
            'price' => floatval($r['price']),
            'on_hand' => floatval($r['on_hand']),
            'min_stock' => floatval($r['minimum_stock']),
            'suggested_qty' => $needed,
            'source' => 'LOCAL'
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $low_items,
        'count' => count($low_items)
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
