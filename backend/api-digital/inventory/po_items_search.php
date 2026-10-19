<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$q = trim($_GET['q'] ?? '');
$limit = intval($_GET['limit'] ?? 50);
if ($limit <= 0) $limit = 50;

$items = [];

try {
    // 1. Search MySQL inv_items
    $sql = "SELECT 
                i.id,
                i.item_code,
                i.item_name,
                COALESCE(u.name, 'ชิ้น') as unit_name,
                i.standard_price,
                i.last_purchase_price,
                i.minimum_stock,
                COALESCE((SELECT SUM(b.balance_qty) FROM inv_stock_balances b WHERE b.item_id = i.id), 0) as on_hand
            FROM inv_items i
            LEFT JOIN inv_units u ON i.unit_id = u.id
            WHERE i.active = 1";
    
    $params = [];
    if (!empty($q)) {
        $sql .= " AND (i.item_code LIKE ? OR i.item_name LIKE ?)";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    $sql .= " ORDER BY i.item_name ASC LIMIT " . intval($limit);

    $stmt = $pdo2->prepare($sql);
    $stmt->execute($params);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $price = floatval($row['last_purchase_price'] > 0 ? $row['last_purchase_price'] : $row['standard_price']);
        $items[] = [
            'item_id' => intval($row['id']),
            'item_code' => $row['item_code'],
            'item_name' => $row['item_name'],
            'unit_name' => $row['unit_name'],
            'price' => $price,
            'on_hand' => floatval($row['on_hand']),
            'min_stock' => floatval($row['minimum_stock']),
            'source' => 'LOCAL'
        ];
    }

    // 2. Also search INVS Medical Supplies (INV_MD / DRUG_GN)
    $sqlSrvHost = "192.168.2.19, 1433";
    $sqlSrvDb   = "invs";
    $sqlSrvUser = "sa";
    $sqlSrvPass = "Cnm@10985";
    try {
        $connSqlSrv = new PDO("sqlsrv:server=$sqlSrvHost;Database=$sqlSrvDb", $sqlSrvUser, $sqlSrvPass);
        $connSqlSrv->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $invsSql = "SELECT TOP $limit 
                        RTRIM(i.WORKING_CODE) as item_code,
                        RTRIM(g.DRUG_NAME) as item_name,
                        RTRIM(i.QTY_UNIT) as unit_name,
                        i.STD_PRICE as price,
                        i.QTY_ON_HAND as on_hand,
                        i.MIN_LEVEL as min_stock
                    FROM INV_MD i
                    LEFT JOIN DRUG_GN g ON RTRIM(LTRIM(i.WORKING_CODE)) = RTRIM(LTRIM(g.WORKING_CODE))
                    WHERE (i.DEPT_ID = 'STOCK2' OR i.DEPT_ID = 'MAIN')";

        if (!empty($q)) {
            $invsSql .= " AND (i.WORKING_CODE LIKE :q1 OR g.DRUG_NAME LIKE :q2)";
        }
        $invsSql .= " ORDER BY g.DRUG_NAME ASC";

        $s = $connSqlSrv->prepare($invsSql);
        if (!empty($q)) {
            $s->bindValue(':q1', "%$q%");
            $s->bindValue(':q2', "%$q%");
        }
        $s->execute();
        $invsRows = $s->fetchAll(PDO::FETCH_ASSOC);

        foreach ($invsRows as $ir) {
            $items[] = [
                'item_id' => 0,
                'item_code' => $ir['item_code'],
                'item_name' => $ir['item_name'],
                'unit_name' => !empty($ir['unit_name']) ? $ir['unit_name'] : 'หน่วย',
                'price' => floatval($ir['price']),
                'on_hand' => floatval($ir['on_hand']),
                'min_stock' => floatval($ir['min_stock']),
                'source' => 'INVS'
            ];
        }
    } catch (Exception $e) {
        // INVS connection error handled silently
    }

    echo json_encode([
        'status' => 'success',
        'data' => $items
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
