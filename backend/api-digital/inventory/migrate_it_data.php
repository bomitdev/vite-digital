<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

try {
    $pdo2->beginTransaction();

    // 1. Check or Create "วัสดุคอมพิวเตอร์" Warehouse
    $stmtWH = $pdo2->prepare("SELECT id FROM inv_warehouses WHERE name LIKE '%คอมพิวเตอร์%' OR name LIKE '%IT%' LIMIT 1");
    $stmtWH->execute();
    $wh = $stmtWH->fetch();

    if ($wh) {
        $warehouse_id = $wh['id'];
    } else {
        $stmtCreateWH = $pdo2->prepare("INSERT INTO inv_warehouses (code, name) VALUES ('WH-IT', 'คลังวัสดุคอมพิวเตอร์ (IT)')");
        $stmtCreateWH->execute();
        $warehouse_id = $pdo2->lastInsertId();
    }

    // 2. Fetch Old Materials
    $stmtOldMat = $pdo2->prepare("SELECT * FROM mt_it_materials");
    $stmtOldMat->execute();
    $old_materials = $stmtOldMat->fetchAll();

    $migrated_items_count = 0;
    $migrated_tx_count = 0;

    foreach ($old_materials as $old_m) {
        $old_id = $old_m['id'];
        $old_code = $old_m['code'];
        $old_name = $old_m['name'];
        $old_type = $old_m['type'] ? $old_m['type'] : 'ทั่วไป';
        $old_unit = $old_m['unit'] ? $old_m['unit'] : 'ชิ้น';
        $old_balance = (float)$old_m['balance'];

        // 3. Handle Category
        $stmtCat = $pdo2->prepare("SELECT id FROM inv_categories WHERE warehouse_id = ? AND name = ? LIMIT 1");
        $stmtCat->execute([$warehouse_id, $old_type]);
        $cat = $stmtCat->fetch();
        if ($cat) {
            $category_id = $cat['id'];
        } else {
            $stmtInsertCat = $pdo2->prepare("INSERT INTO inv_categories (warehouse_id, code, name) VALUES (?, ?, ?)");
            $stmtInsertCat->execute([$warehouse_id, substr(md5($old_type), 0, 4), $old_type]);
            $category_id = $pdo2->lastInsertId();
        }

        // 4. Handle Unit
        $stmtUnit = $pdo2->prepare("SELECT id FROM inv_units WHERE name = ? LIMIT 1");
        $stmtUnit->execute([$old_unit]);
        $unit = $stmtUnit->fetch();
        if ($unit) {
            $unit_id = $unit['id'];
        } else {
            $stmtInsertUnit = $pdo2->prepare("INSERT INTO inv_units (name) VALUES (?)");
            $stmtInsertUnit->execute([$old_unit]);
            $unit_id = $pdo2->lastInsertId();
        }

        // 5. Insert Item Master
        $stmtInsertItem = $pdo2->prepare("
            INSERT INTO inv_items (item_code, old_code, item_name, warehouse_id, category_id, unit_id, lot_control, expiry_control) 
            VALUES (?, ?, ?, ?, ?, ?, 0, 0)
        ");
        $stmtInsertItem->execute([$old_code, $old_code, $old_name, $warehouse_id, $category_id, $unit_id]);
        $new_item_id = $pdo2->lastInsertId();
        $migrated_items_count++;

        // 6. Setup Initial Lot & Stock Balance
        $lot_id = null;
        if ($old_balance > 0) {
            $stmtInsertLot = $pdo2->prepare("
                INSERT INTO inv_stock_lots (warehouse_id, item_id, lot_number, expiry_date, unit_cost, balance_qty)
                VALUES (?, ?, 'MIGRATE-LOT', NULL, 0, ?)
            ");
            $stmtInsertLot->execute([$warehouse_id, $new_item_id, $old_balance]);
            $lot_id = $pdo2->lastInsertId();

            $stmtInsertBal = $pdo2->prepare("
                INSERT INTO inv_stock_balances (warehouse_id, item_id, balance_qty)
                VALUES (?, ?, ?)
            ");
            $stmtInsertBal->execute([$warehouse_id, $new_item_id, $old_balance]);
        }

        // 7. Migrate Historical Transactions for this item
        $stmtOldTx = $pdo2->prepare("SELECT * FROM mt_it_transactions WHERE material_id = ? ORDER BY action_date ASC, id ASC");
        $stmtOldTx->execute([$old_id]);
        $old_txs = $stmtOldTx->fetchAll();

        $running_balance = 0;
        foreach ($old_txs as $tx) {
            $tx_type = $tx['action_type']; // 'IN' or 'OUT'
            $qty = (float)$tx['quantity'];
            $date = $tx['action_date'];
            $user = $tx['user_profile_name'];
            $dest = $tx['reference_dest'];
            $note = $tx['note'];

            if ($tx_type === 'IN') {
                $running_balance += $qty;
                $doc_type = 'RECEIVE';
                $qty_in = $qty;
                $qty_out = 0;
            } else {
                $running_balance -= $qty;
                $doc_type = 'ISSUE';
                $qty_in = 0;
                $qty_out = $qty;
            }

            $stmtLog = $pdo2->prepare("
                INSERT INTO inv_stock_transactions 
                (warehouse_id, item_id, lot_id, document_type, transaction_type, qty_in, qty_out, balance_qty, transaction_date, user_name, remark)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtLog->execute([
                $warehouse_id, $new_item_id, $lot_id, $doc_type, $tx_type, $qty_in, $qty_out, $running_balance, $date, $user, $dest . ' ' . $note
            ]);
            $migrated_tx_count++;
        }

        // 8. Adjustment log if running balance != real balance
        if ($running_balance != $old_balance) {
            $diff = $old_balance - $running_balance;
            $adj_type = $diff > 0 ? 'IN' : 'OUT';
            $adj_qty = abs($diff);
            $qty_in = $diff > 0 ? $adj_qty : 0;
            $qty_out = $diff < 0 ? $adj_qty : 0;

            $stmtLog = $pdo2->prepare("
                INSERT INTO inv_stock_transactions 
                (warehouse_id, item_id, lot_id, document_type, transaction_type, qty_in, qty_out, balance_qty, transaction_date, user_name, remark)
                VALUES (?, ?, ?, 'ADJUST', ?, ?, ?, ?, NOW(), 'System Migration', 'ปรับปรุงยอดยกมาให้ตรงกับความจริง')
            ");
            $stmtLog->execute([
                $warehouse_id, $new_item_id, $lot_id, $adj_type, $qty_in, $qty_out, $old_balance
            ]);
        }
    }

    $pdo2->commit();
    echo json_encode([
        'status' => 'success', 
        'message' => "Migration Completed! Migrated $migrated_items_count items and $migrated_tx_count historical transactions."
    ]);

} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
