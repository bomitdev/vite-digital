<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

try {
    // 1. Vendors list from inv_vendors
    $stmt = $pdo2->query("SELECT id, vendor_code, vendor_name, tax_id, address, phone FROM inv_vendors WHERE active = 1 ORDER BY vendor_name ASC");
    $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If inv_vendors has fewer than 5 rows, also load/sync from INVS COMPANY
    if (count($vendors) < 5) {
        $sqlSrvHost = "192.168.2.19, 1433";
        $sqlSrvDb   = "invs";
        $sqlSrvUser = "sa";
        $sqlSrvPass = "Cnm@10985";
        try {
            $connSqlSrv = new PDO("sqlsrv:server=$sqlSrvHost;Database=$sqlSrvDb", $sqlSrvUser, $sqlSrvPass);
            $connSqlSrv->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $s = $connSqlSrv->query("SELECT RTRIM(COMPANY_CODE) as vendor_code, RTRIM(COMPANY_NAME) as vendor_name, TAX_ID as tax_id, TEL as phone FROM COMPANY WHERE HIDE='N' OR HIDE IS NULL ORDER BY COMPANY_NAME");
            $invs_vendors = $s->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($invs_vendors)) {
                $vendors = $invs_vendors;
            }
        } catch (Exception $e) {}
    }

    // 2. Buy Methods (วิธีจัดซื้อ)
    $buy_methods = [
        ['code' => '13', 'name' => '13 : วิธีเฉพาะเจาะจง', 'default' => true],
        ['code' => '14', 'name' => '14 : วิธีเฉพาะเจาะจง (สัญญา)', 'default' => false],
        ['code' => '8',  'name' => '8 : วิธีประกวดราคาอิเล็กทรอนิกส์ (e-Bidding)', 'default' => false],
        ['code' => '12', 'name' => '12 : วิธีคัดเลือก', 'default' => false],
        ['code' => '1',  'name' => '1 : ตกลงราคา', 'default' => false],
        ['code' => '99', 'name' => '99 : รับบริจาค / รับสนับสนุน', 'default' => false]
    ];

    // 3. Budget Types (ประเภทงบประมาณ)
    $budget_types = [
        ['code' => '30', 'name' => '30 : งบเงินบำรุง', 'default' => true],
        ['code' => '10', 'name' => '10 : งบประมาณแผ่นดิน', 'default' => false],
        ['code' => '20', 'name' => '20 : งบค่าบริการทางการแพทย์ (UC)', 'default' => false],
        ['code' => '40', 'name' => '40 : เงินบริจาค', 'default' => false]
    ];

    // 4. Common Types (รูปแบบจัดซื้อ)
    $buy_commons = [
        ['code' => '0', 'name' => '0 : ซื้อเอง', 'default' => true],
        ['code' => '1', 'name' => '1 : ซื้อร่วมจังหวัด', 'default' => false],
        ['code' => '2', 'name' => '2 : ซื้อร่วมเขต', 'default' => false],
        ['code' => '3', 'name' => '3 : ซื้อร่วมกระทรวง', 'default' => false]
    ];

    // 5. Price Criteria (เกณฑ์ราคา)
    $price_criterias = [
        'ราคาครั้งก่อน',
        'ราคากลาง',
        'ราคาตามสัญญา',
        'ราคามาตรฐานกระทรวงการคลัง'
    ];

    echo json_encode([
        'status' => 'success',
        'data' => [
            'vendors' => $vendors,
            'buy_methods' => $buy_methods,
            'budget_types' => $budget_types,
            'buy_commons' => $buy_commons,
            'price_criterias' => $price_criterias,
            'default_memo_no' => 'อย 0033.301/132',
            'default_delivery_days' => 30
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
