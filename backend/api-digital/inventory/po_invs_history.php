<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json; charset=utf-8");

// ยกเลิกการดึงข้อมูลประวัติ INVS ชั่วคราว
echo json_encode(['status' => 'success', 'data' => [], 'count' => 0, 'message' => 'Temporarily disabled'], JSON_UNESCAPED_UNICODE);
exit;

$search = trim($_GET['search'] ?? '');
$limit  = intval($_GET['limit'] ?? 100);
if ($limit <= 0 || $limit > 500) $limit = 100;

$sqlSrvHost = "192.168.2.19, 1433";
$sqlSrvDb   = "invs";
$sqlSrvUser = "sa";
$sqlSrvPass = "Cnm@10985";

try {
    $connSqlSrv = new PDO("sqlsrv:server=$sqlSrvHost;Database=$sqlSrvDb", $sqlSrvUser, $sqlSrvPass);
    $connSqlSrv->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "SELECT TOP $limit 
                RTRIM(p.PO_NO) as po_no,
                p.PO_DATE as po_date,
                p.TOTAL_COST as total_cost,
                p.TOTAL_ITEM as total_item,
                RTRIM(p.BUDGET_TYPE) as budget_type,
                RTRIM(p.BUY_METHOD) as buy_method,
                RTRIM(p.BUY_COMMON) as buy_common,
                RTRIM(p.REQ_APP_NO) as req_app_no,
                p.REQ_APP_DATE as req_app_date,
                RTRIM(p.AIC_NAME) as aic_name,
                RTRIM(p.REMARK) as remark,
                RTRIM(p.USER_ID) as user_id,
                RTRIM(p.MOD_SYS) as mod_sys,
                RTRIM(c.COMPANY_NAME) as vendor_name,
                RTRIM(c.COMPANY_CODE) as vendor_code
            FROM MS_PO p 
            LEFT JOIN COMPANY c ON RTRIM(LTRIM(p.VENDOR_CODE)) = RTRIM(LTRIM(c.COMPANY_CODE))
            WHERE 1=1";

    if (!empty($search)) {
        $sql .= " AND (p.PO_NO LIKE :s1 OR c.COMPANY_NAME LIKE :s2 OR p.REQ_APP_NO LIKE :s3 OR p.REMARK LIKE :s4)";
    }

    $sql .= " ORDER BY p.PO_DATE DESC, p.PO_NO DESC";

    $stmt = $connSqlSrv->prepare($sql);
    if (!empty($search)) {
        $stmt->bindValue(':s1', "%$search%");
        $stmt->bindValue(':s2', "%$search%");
        $stmt->bindValue(':s3', "%$search%");
        $stmt->bindValue(':s4', "%$search%");
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format dates and buy method labels
    $methodNames = [
        '13' => '13 : วิธีเฉพาะเจาะจง',
        '14' => '14 : วิธีเฉพาะเจาะจง (สัญญา)',
        '8'  => '8 : e-Bidding',
        '12' => '12 : วิธีคัดเลือก',
        '1'  => '1 : ตกลงราคา',
        '99' => '99 : บริจาค'
    ];

    $data = [];
    foreach ($rows as $r) {
        $d = $r['po_date'];
        $formattedDate = $d;
        if (strlen($d) === 8) {
            $formattedDate = substr($d, 0, 4) . '-' . substr($d, 4, 2) . '-' . substr($d, 6, 2);
        }

        $methodCode = $r['buy_method'];
        $data[] = [
            'po_no' => $r['po_no'],
            'po_date' => $formattedDate,
            'vendor_name' => $r['vendor_name'] ?: 'ไม่ระบุ',
            'vendor_code' => $r['vendor_code'],
            'total_cost' => floatval($r['total_cost']),
            'total_item' => intval($r['total_item']),
            'budget_type' => $r['budget_type'] == '30' ? '30 : งบเงินบำรุง' : $r['budget_type'],
            'buy_method' => $methodNames[$methodCode] ?? $methodCode,
            'buy_common' => $r['buy_common'] == '0' ? '0 : ซื้อเอง' : $r['buy_common'],
            'req_app_no' => $r['req_app_no'],
            'aic_name' => $r['aic_name'],
            'remark' => $r['remark'],
            'user_id' => $r['user_id']
        ];
    }

    echo json_encode(['status' => 'success', 'data' => $data, 'count' => count($data)], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
