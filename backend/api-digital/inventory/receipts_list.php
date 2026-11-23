<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';
require_once __DIR__ . '/inv_auth_helper.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $id = (int)($input['id'] ?? 0);
        $newDateRaw = trim((string)($input['receipt_date'] ?? ''));

        if ($id <= 0 || $newDateRaw === '') {
            echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุข้อมูลและวันที่รับเข้าให้ครบถ้วน']);
            exit;
        }

        $ts = strtotime($newDateRaw);
        if ($ts === false) {
            echo json_encode(['status' => 'error', 'message' => 'รูปแบบวันที่ไม่ถูกต้อง']);
            exit;
        }
        $formattedDate = date('Y-m-d H:i:s', $ts);

        $stCheck = $pdo2->prepare("SELECT id, warehouse_id, receipt_no FROM inv_receipts WHERE id = ? LIMIT 1");
        $stCheck->execute([$id]);
        $rc = $stCheck->fetch(PDO::FETCH_ASSOC);
        if (!$rc) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลใบรับเข้า']);
            exit;
        }

        $currentUser = getInvCurrentUser($pdo3 ?? null);
        if (!isInvWarehouseManager($pdo2, $pdo3 ?? null, (int)$rc['warehouse_id'], $currentUser)) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'เฉพาะผู้ดูแลระบบหรือผู้จัดการคลังเท่านั้นที่แก้ไขวันที่รับเข้าได้']);
            exit;
        }

        $pdo2->beginTransaction();
        $stUpdRc = $pdo2->prepare("UPDATE inv_receipts SET receipt_date = ? WHERE id = ?");
        $stUpdRc->execute([$formattedDate, $id]);

        $stUpdTx = $pdo2->prepare("UPDATE inv_stock_transactions SET transaction_date = ? WHERE document_type IN ('RECEIVE', 'RECEIPT') AND document_id = ?");
        $stUpdTx->execute([$formattedDate, $id]);
        $pdo2->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'แก้ไขวันที่รับเข้าเรียบร้อยแล้ว',
            'receipt_date' => $formattedDate
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id > 0) {
        $stmt = $pdo2->prepare("
            SELECT r.*, COALESCE(v.vendor_name, '') AS vendor_name, COALESCE(w.name, '') AS warehouse_name
            FROM inv_receipts r
            LEFT JOIN inv_vendors v ON r.vendor_id = v.id
            LEFT JOIN inv_warehouses w ON r.warehouse_id = w.id
            WHERE r.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $receipt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$receipt) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลใบรับเข้า']);
            exit;
        }

        $itemStmt = $pdo2->prepare("
            SELECT ri.*, i.item_code, i.item_name, COALESCE(u.name, 'หน่วย') AS unit_name
            FROM inv_receipt_items ri
            LEFT JOIN inv_items i ON ri.item_id = i.id
            LEFT JOIN inv_units u ON COALESCE(ri.unit_id, i.unit_id) = u.id
            WHERE ri.receipt_id = ?
            ORDER BY ri.id ASC
        ");
        $itemStmt->execute([$id]);
        $receipt['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'data' => $receipt], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $warehouse_id = isset($_GET['warehouse_id']) && $_GET['warehouse_id'] !== '' ? (int)$_GET['warehouse_id'] : 0;
    $search = trim((string)($_GET['search'] ?? ''));
    $limit = isset($_GET['limit']) ? max(10, min(200, (int)$_GET['limit'])) : 50;

    $where = ["1=1"];
    $params = [];

    if ($warehouse_id > 0) {
        $where[] = "r.warehouse_id = ?";
        $params[] = $warehouse_id;
    }

    if ($search !== '') {
        $where[] = "(r.receipt_no LIKE ? OR r.invoice_no LIKE ? OR v.vendor_name LIKE ? OR r.receiver_name LIKE ? OR r.remark LIKE ?)";
        $like = "%{$search}%";
        array_push($params, $like, $like, $like, $like, $like);
    }

    $sql = "
        SELECT r.id, r.receipt_no, r.receipt_date, r.warehouse_id, r.invoice_no,
               r.total_amount, r.receiver_name, r.remark, r.attachment_url, r.attachment_name, r.status,
               COALESCE(v.vendor_name, '') AS vendor_name,
               COALESCE(w.name, '') AS warehouse_name,
               (SELECT COUNT(*) FROM inv_receipt_items ri WHERE ri.receipt_id = r.id) AS item_count
        FROM inv_receipts r
        LEFT JOIN inv_vendors v ON r.vendor_id = v.id
        LEFT JOIN inv_warehouses w ON r.warehouse_id = w.id
        WHERE " . implode(" AND ", $where) . "
        ORDER BY r.receipt_date DESC, r.id DESC
        LIMIT {$limit}
    ";

    $stmt = $pdo2->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

