<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$low_items = [];

try {
    // 1. MySQL low stock items
    $sql = "SELECT 
                i.id as item_id,
                i.item_code,
                i.item_name,
                COALESCE(u.name, 'ชิ้น') as unit_name,
                i.minimum_stock,
                i.reorder_point,
                COALESCE(i.last_purchase_price, i.standard_price) as price,
                COALESCE((SELECT SUM(b.balance_qty) FROM inv_stock_balances b WHERE b.item_id = i.id), 0) as on_hand
            FROM inv_items i
            LEFT JOIN inv_units u ON i.unit_id = u.id
            WHERE i.active = 1
            HAVING (on_hand <= minimum_stock OR on_hand = 0)
            ORDER BY on_hand ASC
            LIMIT 50";
    $stmt = $pdo2->query($sql);
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $needed = ($r['minimum_stock'] > $r['on_hand']) ? ($r['minimum_stock'] * 2 - $r['on_hand']) : 10;
        if ($needed <= 0) $needed = 1;
        $low_items[] = [
            'item_id' => intval($r['item_id']),
            'item_code' => $r['item_code'],
            'item_name' => $r['item_name'],
            'unit_name' => $r['unit_name'],
            'price' => floatval($r['price']),
            'on_hand' => floatval($r['on_hand']),
            'min_stock' => floatval($r['minimum_stock']),
            'suggested_qty' => $needed,
            'source' => 'LOCAL'
        ];
    }

    // 2. INVS low stock / zero on-hand items
    $sqlSrvHost = "192.168.2.19, 1433";
    $sqlSrvDb   = "invs";
    $sqlSrvUser = "sa";
    $sqlSrvPass = "Cnm@10985";
    try {
        $connSqlSrv = new PDO("sqlsrv:server=$sqlSrvHost;Database=$sqlSrvDb", $sqlSrvUser, $sqlSrvPass);
        $connSqlSrv->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $invsSql = "SELECT TOP 50 
                        RTRIM(i.WORKING_CODE) as item_code,
                        RTRIM(g.DRUG_NAME) as item_name,
                        RTRIM(i.QTY_UNIT) as unit_name,
                        i.STD_PRICE as price,
                        i.QTY_ON_HAND as on_hand,
                        i.MIN_LEVEL as min_stock,
                        i.REORDER_QTY as suggested_qty
                    FROM INV_MD i
                    LEFT JOIN DRUG_GN g ON RTRIM(LTRIM(i.WORKING_CODE)) = RTRIM(LTRIM(g.WORKING_CODE))
                    WHERE (i.DEPT_ID = 'STOCK2' OR i.DEPT_ID = 'MAIN')
                      AND (i.QTY_ON_HAND <= 5 OR (i.MIN_LEVEL > 0 AND i.QTY_ON_HAND <= i.MIN_LEVEL))
                    ORDER BY i.QTY_ON_HAND ASC";
        $s = $connSqlSrv->query($invsSql);
        $invsRows = $s->fetchAll(PDO::FETCH_ASSOC);

        foreach ($invsRows as $ir) {
            $sug = floatval($ir['suggested_qty']);
            if ($sug <= 0) $sug = 10;
            $low_items[] = [
                'item_id' => 0,
                'item_code' => $ir['item_code'],
                'item_name' => $ir['item_name'],
                'unit_name' => !empty($ir['unit_name']) ? $ir['unit_name'] : 'หน่วย',
                'price' => floatval($ir['price']),
                'on_hand' => floatval($ir['on_hand']),
                'min_stock' => floatval($ir['min_stock']),
                'suggested_qty' => $sug,
                'source' => 'INVS'
            ];
        }
    } catch (Exception $e) {}

    echo json_encode([
        'status' => 'success',
        'data' => $low_items,
        'count' => count($low_items)
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
