<?php
require_once '../../config.php';
require_once '../../cors.php';
require_once __DIR__ . '/inv_log_helper.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Only POST is allowed.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

// Validate main inputs
$warehouse_id = (int)($data['warehouse_id'] ?? 0);
$receipt_date = $data['receipt_date'] ?? date('Y-m-d H:i:s');
$receipt_no = trim($data['receipt_no'] ?? '');
$vendor_id = isset($data['vendor_id']) && $data['vendor_id'] !== '' ? (int)$data['vendor_id'] : null;
$po_id = isset($data['po_id']) && $data['po_id'] !== '' ? (int)$data['po_id'] : null;
$invoice_no = trim($data['invoice_no'] ?? '');
$remark = trim($data['remark'] ?? '');
$user_name = trim($data['user_name'] ?? 'System');
$items = $data['items'] ?? [];

if (!$warehouse_id || !$receipt_no || empty($items)) {
    echo json_encode(['status' => 'error', 'message' => 'Warehouse, Receipt No, and Items are required.']);
    exit;
}

try {
    $pdo2->beginTransaction();

    // Calculate total amount
    $total_amount = 0;
    foreach ($items as $item) {
        $qty = (float)($item['qty'] ?? 0);
        $cost = (float)($item['unit_cost'] ?? 0);
        $total_amount += ($qty * $cost);
    }

    // 1. Insert Receipt
    $stmt = $pdo2->prepare("
        INSERT INTO inv_receipts 
        (receipt_no, receipt_date, warehouse_id, vendor_id, po_id, invoice_no, total_amount, receiver_name, remark, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'COMPLETED')
    ");
    $stmt->execute([
        $receipt_no, $receipt_date, $warehouse_id, $vendor_id, $po_id, $invoice_no, $total_amount, $user_name, $remark
    ]);
    $receipt_id = $pdo2->lastInsertId();

    // Statements for inside the loop
    $stmtReceiptItem = $pdo2->prepare("
        INSERT INTO inv_receipt_items (receipt_id, item_id, lot_number, expiry_date, qty, unit_id, unit_cost, total_price)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmtGetLot = $pdo2->prepare("SELECT id, balance_qty FROM inv_stock_lots WHERE item_id = ? AND warehouse_id = ? AND lot_number = ? LIMIT 1");
    $stmtInsertLot = $pdo2->prepare("INSERT INTO inv_stock_lots (item_id, warehouse_id, lot_number, expiry_date, unit_cost, receive_qty, balance_qty) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmtUpdateLot = $pdo2->prepare("UPDATE inv_stock_lots SET balance_qty = balance_qty + ?, receive_qty = receive_qty + ? WHERE id = ?");
    
    $stmtGetBalance = $pdo2->prepare("SELECT balance_qty FROM inv_stock_balances WHERE warehouse_id = ? AND item_id = ? LIMIT 1");
    $stmtInsertBalance = $pdo2->prepare("INSERT INTO inv_stock_balances (warehouse_id, item_id, balance_qty) VALUES (?, ?, ?)");
    $stmtUpdateBalance = $pdo2->prepare("UPDATE inv_stock_balances SET balance_qty = balance_qty + ? WHERE warehouse_id = ? AND item_id = ?");
    
    $stmtTx = $pdo2->prepare("
        INSERT INTO inv_stock_transactions 
        (warehouse_id, item_id, lot_id, document_type, document_id, transaction_type, qty_in, unit_cost, balance_qty, transaction_date, user_name, remark)
        VALUES (?, ?, ?, 'RECEIVE', ?, 'IN', ?, ?, ?, ?, ?, ?)
    ");
    
    $stmtUpdatePOItem = null;
    if ($po_id) {
        $stmtUpdatePOItem = $pdo2->prepare("UPDATE inv_purchase_order_items SET qty_received = COALESCE(qty_received, 0) + ? WHERE po_id = ? AND item_id = ?");
    }

    // Preload warehouse categories & item types for smart auto-registration when importing/receiving new items
    $whCategories = [];
    $stmtCatList = $pdo2->prepare("SELECT id, code, name FROM inv_categories WHERE warehouse_id = ?");
    $stmtCatList->execute([$warehouse_id]);
    foreach ($stmtCatList->fetchAll(PDO::FETCH_ASSOC) as $cRow) {
        $whCategories[] = $cRow;
    }

    $whItemTypes = [];
    $stmtTypeList = $pdo2->prepare("SELECT id, code, name FROM inv_item_types WHERE active = 1 AND (warehouse_id = ? OR warehouse_id IS NULL) ORDER BY warehouse_id DESC");
    $stmtTypeList->execute([$warehouse_id]);
    foreach ($stmtTypeList->fetchAll(PDO::FETCH_ASSOC) as $tRow) {
        $whItemTypes[] = $tRow;
    }

    // 2. Process Items
    foreach ($items as $item) {
        $po_item_id = !empty($item['po_item_id']) ? (int)$item['po_item_id'] : null;
        $item_id = (int)($item['item_id'] ?? 0);
        $qty = (float)($item['qty'] ?? 0);
        $unit_id = (int)($item['unit_id'] ?? 0);
        $unit_cost = (float)($item['unit_cost'] ?? 0);
        $total_price = $qty * $unit_cost;
        $lot_number = trim((string)($item['lot_number'] ?? ''));
        $expiry_date = !empty($item['expiry_date']) ? trim((string)$item['expiry_date']) : null;
        $item_code_input = trim((string)($item['item_code'] ?? ''));
        $item_name = trim((string)($item['item_name'] ?? ''));
        $item_name_en = trim((string)($item['item_name_en'] ?? ''));
        $category_name_input = trim((string)($item['category_name'] ?? ''));
        $item_type_name_input = trim((string)($item['item_type_name'] ?? ''));

        // Auto-resolve unit_id if missing or 0
        if ($unit_id <= 0 && !empty($item['unit_name'])) {
            $unitName = trim((string)$item['unit_name']);
            $uStmt = $pdo2->prepare("SELECT id FROM inv_units WHERE name = ? AND (warehouse_id = ? OR warehouse_id IS NULL) ORDER BY warehouse_id DESC LIMIT 1");
            $uStmt->execute([$unitName, $warehouse_id]);
            $unit_id = (int)$uStmt->fetchColumn();
            if (!$unit_id) {
                // Find next unit code for this warehouse
                $stmtMax = $pdo2->prepare("SELECT MAX(CAST(SUBSTRING(code, 2) AS UNSIGNED)) as max_num FROM inv_units WHERE warehouse_id <=> ? AND code LIKE 'U%'");
                $stmtMax->execute([$warehouse_id]);
                $nextNum = (int)$stmtMax->fetchColumn() + 1;
                $uCode = 'U' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

                $insU = $pdo2->prepare("INSERT INTO inv_units (warehouse_id, name, code, active) VALUES (?, ?, ?, 1)");
                $insU->execute([$warehouse_id, $unitName, $uCode]);
                $unit_id = (int)$pdo2->lastInsertId();
            }
        }

        // Auto-resolve or register item_id if 0 or null (e.g. from Excel/CSV import or AI bill)
        if ($item_id <= 0 && ($item_code_input !== '' || $item_name !== '')) {
            $existingItemId = 0;

            if ($item_code_input !== '') {
                $chkByCode = $pdo2->prepare("SELECT id FROM inv_items WHERE warehouse_id = ? AND item_code = ? LIMIT 1");
                $chkByCode->execute([$warehouse_id, $item_code_input]);
                $existingItemId = (int)$chkByCode->fetchColumn();
            }

            if ($existingItemId <= 0 && $item_code_input === '' && $item_name !== '') {
                $chkItem = $pdo2->prepare("SELECT id FROM inv_items WHERE warehouse_id = ? AND (item_name = ? OR item_name_en = ?) LIMIT 1");
                $chkItem->execute([$warehouse_id, $item_name, $item_name]);
                $existingItemId = (int)$chkItem->fetchColumn();
            }

            if ($existingItemId > 0) {
                $item_id = $existingItemId;
            } elseif ($item_name !== '') {
                // Resolve category_id (explicit ID -> name/code match -> HK/OS prefix match)
                $category_id = !empty($item['category_id']) ? (int)$item['category_id'] : null;
                if (!$category_id && $category_name_input !== '') {
                    $catNeedle = mb_strtolower($category_name_input);
                    foreach ($whCategories as $cRow) {
                        if (mb_strtolower(trim($cRow['name'])) === $catNeedle || mb_strtolower(trim((string)$cRow['code'])) === $catNeedle) {
                            $category_id = (int)$cRow['id'];
                            break;
                        }
                    }
                }
                if (!$category_id && $item_code_input !== '') {
                    $upperCode = strtoupper($item_code_input);
                    $hint = str_starts_with($upperCode, 'HK') ? 'งานบ้าน' : (str_starts_with($upperCode, 'OS') ? 'สำนักงาน' : '');
                    if ($hint !== '') {
                        foreach ($whCategories as $cRow) {
                            if (mb_strpos($cRow['name'], $hint) !== false) {
                                $category_id = (int)$cRow['id'];
                                break;
                            }
                        }
                    }
                }

                // Resolve item_type_id (explicit ID -> name/code match -> HK/OS prefix match)
                $item_type_id = !empty($item['item_type_id']) ? (int)$item['item_type_id'] : null;
                if (!$item_type_id && $item_type_name_input !== '') {
                    $typeNeedle = mb_strtolower($item_type_name_input);
                    foreach ($whItemTypes as $tRow) {
                        if (mb_strtolower(trim($tRow['name'])) === $typeNeedle || mb_strtolower(trim((string)$tRow['code'])) === $typeNeedle) {
                            $item_type_id = (int)$tRow['id'];
                            break;
                        }
                    }
                }
                if (!$item_type_id && $item_code_input !== '') {
                    $upperCode = strtoupper($item_code_input);
                    $hint = str_starts_with($upperCode, 'HK') ? 'งานบ้าน' : (str_starts_with($upperCode, 'OS') ? 'สำนักงาน' : '');
                    if ($hint !== '') {
                        foreach ($whItemTypes as $tRow) {
                            if (mb_strpos($tRow['name'], $hint) !== false) {
                                $item_type_id = (int)$tRow['id'];
                                break;
                            }
                        }
                    }
                }

                // Preserve caller's item_code if supplied; otherwise generate next sequential code
                $newItemCode = $item_code_input;
                if ($newItemCode === '') {
                    $catPrefix = 'C';
                    if ($category_id) {
                        foreach ($whCategories as $cRow) {
                            if ((int)$cRow['id'] === $category_id && preg_match('/^([A-Za-z]+)/', trim((string)$cRow['code']), $cm)) {
                                $catPrefix = strtoupper($cm[1]);
                                break;
                            }
                        }
                    }
                    $maxCodeStmt = $pdo2->prepare("SELECT item_code FROM inv_items WHERE warehouse_id = ? AND item_code LIKE ? ORDER BY id DESC LIMIT 1");
                    $maxCodeStmt->execute([$warehouse_id, $catPrefix . '%']);
                    $lastCode = $maxCodeStmt->fetchColumn();
                    $nextNum = 1;
                    if ($lastCode && preg_match('/(\d+)$/', $lastCode, $mDigits)) {
                        $nextNum = (int)$mDigits[1] + 1;
                    }
                    $newItemCode = $catPrefix . str_pad((string)$nextNum, 3, '0', STR_PAD_LEFT);
                }

                $lotControlFlag = isset($item['lot_control']) ? (int)(bool)$item['lot_control'] : ($lot_number !== '' ? 1 : 0);
                $expControlFlag = isset($item['expiry_control']) ? (int)(bool)$item['expiry_control'] : ($expiry_date ? 1 : 0);

                $insItem = $pdo2->prepare("
                    INSERT INTO inv_items
                        (item_code, item_name, item_name_en, category_id, item_type_id, warehouse_id, unit_id, purchase_unit_id, issue_unit_id, conversion_rate, standard_price, last_purchase_price, lot_control, expiry_control, active)
                    VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?, ?, 1.0000, ?, ?, ?, ?, 1)
                ");
                $effUnitId = $unit_id > 0 ? $unit_id : null;
                $insItem->execute([
                    $newItemCode,
                    $item_name,
                    $item_name_en ?: null,
                    $category_id,
                    $item_type_id,
                    $warehouse_id,
                    $effUnitId,
                    $effUnitId,
                    $effUnitId,
                    $unit_cost,
                    $unit_cost,
                    $lotControlFlag,
                    $expControlFlag,
                ]);
                $item_id = (int)$pdo2->lastInsertId();
            }

            // If linked to PO item, update po item's item_id
            if ($po_item_id && $item_id > 0) {
                $pdo2->prepare("UPDATE inv_purchase_order_items SET item_id = ? WHERE id = ?")->execute([$item_id, $po_item_id]);
            }
        }

        // Rows with qty <= 0 only ensure master registration (no 0-qty receipt/stock transaction)
        if ($qty <= 0 || $item_id <= 0) {
            continue;
        }

        // Insert Receipt Item
        $stmtReceiptItem->execute([$receipt_id, $item_id, $lot_number, $expiry_date, $qty, $unit_id, $unit_cost, $total_price]);
        
        // Manage Lot
        $lot_id = null;
        if ($lot_number !== '') {
            $stmtGetLot->execute([$item_id, $warehouse_id, $lot_number]);
            $existing_lot = $stmtGetLot->fetch();
            
            if ($existing_lot) {
                $lot_id = $existing_lot['id'];
                $stmtUpdateLot->execute([$qty, $qty, $lot_id]);
            } else {
                $stmtInsertLot->execute([$item_id, $warehouse_id, $lot_number, $expiry_date, $unit_cost, $qty, $qty]);
                $lot_id = $pdo2->lastInsertId();
            }
        }
        
        // Manage Stock Balance
        $stmtGetBalance->execute([$warehouse_id, $item_id]);
        $existing_balance = $stmtGetBalance->fetch();
        
        $new_balance = $qty;
        if ($existing_balance) {
            $new_balance += (float)$existing_balance['balance_qty'];
            $stmtUpdateBalance->execute([$qty, $warehouse_id, $item_id]);
        } else {
            $stmtInsertBalance->execute([$warehouse_id, $item_id, $qty]);
        }
        
        // Insert Stock Transaction
        $stmtTx->execute([
            $warehouse_id, $item_id, $lot_id, $receipt_id, $qty, $unit_cost, $new_balance, $receipt_date, $user_name, 'RECEIVE from ' . $receipt_no
        ]);
        
        // Update PO Item received qty
        if ($po_item_id) {
            $pdo2->prepare("UPDATE inv_purchase_order_items SET qty_received = COALESCE(qty_received, 0) + ? WHERE id = ?")->execute([$qty, $po_item_id]);
        } elseif ($stmtUpdatePOItem && $item_id > 0) {
            $stmtUpdatePOItem->execute([$qty, $po_id, $item_id]);
        }

        // Update item's last purchase price
        if ($item_id > 0 && $unit_cost > 0) {
            $pdo2->prepare("UPDATE inv_items SET last_purchase_price = ? WHERE id = ?")->execute([$unit_cost, $item_id]);
        }
    }
    
    // 3. Update PO Status
    if ($po_id) {
        $stmtCheckPO = $pdo2->prepare("SELECT COUNT(*) FROM inv_purchase_order_items WHERE po_id = ? AND qty_ordered > COALESCE(qty_received, 0)");
        $stmtCheckPO->execute([$po_id]);
        $unreceived = (int)$stmtCheckPO->fetchColumn();
        
        if ($unreceived > 0) {
            $stmtUpdatePOStatus = $pdo2->prepare("UPDATE inv_purchase_orders SET status = 'PARTIAL_RECEIVED' WHERE id = ?");
        } else {
            $stmtUpdatePOStatus = $pdo2->prepare("UPDATE inv_purchase_orders SET status = 'COMPLETED' WHERE id = ?");
        }
        $stmtUpdatePOStatus->execute([$po_id]);
    }

    $pdo2->commit();
    recordInvActivityLog($pdo2, [
        'warehouse_id' => $warehouse_id,
        'user_fullname' => $user_name,
        'page_code' => 'receive',
        'page_name' => 'ตรวจรับพัสดุเข้าคลัง',
        'action' => 'RECEIVE_STOCK',
        'target_type' => 'receipt',
        'target_id' => (string)$receipt_id,
        'details' => "ตรวจรับพัสดุเข้าคลัง เลขที่ {$receipt_no} จำนวน " . count($items) . " รายการ (มูลค่า " . number_format((float)$total_amount, 2) . " บาท)",
        'meta' => ['receipt_no' => $receipt_no, 'po_id' => $po_id, 'invoice_no' => $invoice_no, 'items_count' => count($items), 'total_amount' => $total_amount],
    ], $pdo3 ?? null);
    echo json_encode(['status' => 'success', 'message' => 'Stock Received successfully', 'receipt_id' => $receipt_id]);

} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
