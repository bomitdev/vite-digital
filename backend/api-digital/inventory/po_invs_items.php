<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json; charset=utf-8");

$po_no = trim($_GET['po_no'] ?? '');

if (empty($po_no)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing PO Number']);
    exit;
}

$sqlSrvHost = "192.168.2.19, 1433";
$sqlSrvDb   = "invs";
$sqlSrvUser = "sa";
$sqlSrvPass = "Cnm@10985";

try {
    $connSqlSrv = new PDO("sqlsrv:server=$sqlSrvHost;Database=$sqlSrvDb", $sqlSrvUser, $sqlSrvPass);
    $connSqlSrv->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT 
                RTRIM(c.WORKING_CODE) as item_code,
                RTRIM(g.DRUG_NAME) as item_name,
                c.QTY_ORDER as qty_ordered,
                c.QTY_ORDER_RCV as qty_received,
                c.BUY_UNIT_COST as unit_price,
                c.COST as total_price,
                c.PACK_RATIO as pack_ratio,
                RTRIM(c.RCV_FLAG) as rcv_flag,
                RTRIM(i.QTY_UNIT) as unit_name
            FROM MS_PO_C c
            LEFT JOIN DRUG_GN g ON RTRIM(LTRIM(c.WORKING_CODE)) = RTRIM(LTRIM(g.WORKING_CODE))
            LEFT JOIN INV_MD i ON RTRIM(LTRIM(c.WORKING_CODE)) = RTRIM(LTRIM(i.WORKING_CODE)) AND (i.DEPT_ID='STOCK2' OR i.DEPT_ID='MAIN')
            WHERE RTRIM(LTRIM(c.PO_NO)) = :po_no";

    $stmt = $connSqlSrv->prepare($sql);
    $stmt->execute([':po_no' => $po_no]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($items as $item) {
        $data[] = [
            'item_code' => $item['item_code'],
            'item_name' => $item['item_name'] ?: 'เวชภัณฑ์รหัส ' . $item['item_code'],
            'qty_ordered' => floatval($item['qty_ordered']),
            'qty_received' => floatval($item['qty_received']),
            'unit_price' => floatval($item['unit_price']),
            'total_price' => floatval($item['total_price']),
            'pack_ratio' => floatval($item['pack_ratio']),
            'rcv_flag' => $item['rcv_flag'] === 'Y' ? 'รับครบแล้ว' : 'ค้างรับ',
            'unit_name' => $item['unit_name'] ?: 'หน่วย'
        ];
    }

    echo json_encode(['status' => 'success', 'data' => $data], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
