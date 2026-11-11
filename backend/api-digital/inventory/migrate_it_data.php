<?php
/**
 * ============================================================================
 * IT Inventory Migration Engine (Legacy to Warehouse ID 1)
 * ============================================================================
 * Source Tables: mt_it_materials, mt_it_transactions, mt_it_lots, mt_it_categories
 * Target Tables: inv_warehouses, inv_categories, inv_units, inv_items, 
 *                inv_stock_lots, inv_stock_balances, inv_stock_transactions
 * 
 * Execution:
 *   CLI:  php backend/api-digital/inventory/migrate_it_data.php [--dry-run] [--force-fresh] [--help]
 *   HTTP: GET /backend/api-digital/inventory/migrate_it_data.php?dry_run=1&force_fresh=1
 * ============================================================================
 */

declare(strict_types=1);

// Safe path inclusion - Must be first line before any output
require_once __DIR__ . '/../../config.php';

// Detect SAPI Execution Mode
$isCli = (PHP_SAPI === 'cli' || php_sapi_name() === 'cli');

// Parse Execution Flags
$isDryRun = false;
$isForceFresh = false;

if ($isCli) {
    global $argv;
    $allowedCliOptions = ['--dry-run', '--force-fresh', '--help', '-h'];
    $passedArgs = array_slice($argv ?? [], 1);

    $usageText = "IT Inventory Migration Tool (Warehouse ID 1)\n"
               . "Usage: php migrate_it_data.php [options]\n"
               . "Options:\n"
               . "  --dry-run       Validate migration without persisting changes to database\n"
               . "  --force-fresh   Purge previously migrated legacy records for WH 1 before re-importing\n"
               . "  --help, -h      Display this help guide\n";

    // Validate CLI arguments against whitelist
    foreach ($passedArgs as $arg) {
        if (!in_array($arg, $allowedCliOptions, true)) {
            $stderr = defined('STDERR') ? STDERR : fopen('php://stderr', 'w');
            fwrite($stderr, "[ERROR] Unrecognized option '{$arg}'\n\n");
            fwrite($stderr, $usageText);
            exit(1);
        }
    }

    // Help option
    if (in_array('--help', $passedArgs, true) || in_array('-h', $passedArgs, true)) {
        echo $usageText;
        exit(0);
    }

    $isDryRun = in_array('--dry-run', $passedArgs, true);
    $isForceFresh = in_array('--force-fresh', $passedArgs, true);
} else {
    // Whitelist of allowed HTTP query parameters
    $allowedParams = ['dry_run', 'force_fresh', 'help'];

    // Validate that no unrecognized query parameters were passed
    $invalidParams = array_diff(array_keys($_GET), $allowedParams);
    if (!empty($invalidParams)) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'error',
            'message' => 'Unrecognized query parameter: ' . implode(', ', $invalidParams),
            'allowed_parameters' => $allowedParams
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // Help documentation endpoint
    if (isset($_GET['help'])) {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'help',
            'description' => 'IT Inventory Migration Tool (Warehouse ID 1)',
            'usage' => [
                'endpoint' => 'GET /backend/api-digital/inventory/migrate_it_data.php',
                'parameters' => [
                    'dry_run' => 'Validate migration without persisting changes to database (values: 1, true, yes)',
                    'force_fresh' => 'Purge previously migrated legacy records for WH 1 before re-importing (values: 1, true, yes)',
                    'help' => 'Display this API documentation guide'
                ]
            ],
            'allowed_parameters' => $allowedParams
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // Parse boolean execution flags
    $isDryRun = isset($_GET['dry_run']) 
        && is_scalar($_GET['dry_run']) 
        && in_array(strtolower((string)$_GET['dry_run']), ['1', 'true', 'yes', ''], true);
    $isForceFresh = isset($_GET['force_fresh']) 
        && is_scalar($_GET['force_fresh']) 
        && in_array(strtolower((string)$_GET['force_fresh']), ['1', 'true', 'yes', ''], true);
}

// Pre-flight Connection Verification
if (!isset($pdo2) || !($pdo2 instanceof PDO)) {
    $msg = 'Database connection error: $pdo2 is not initialized in config.php.';
    if ($isCli) {
        fwrite(STDERR, "[CRITICAL] $msg\n");
        exit(1);
    } else {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => $msg], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Strict PDO configuration
$pdo2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo2->exec("SET NAMES utf8mb4");

// Migration Logger Abstraction
class MigrationLogger {
    private bool $isCli;
    private array $logs = [];
    private float $startTime;

    public function __construct(bool $isCli) {
        $this->isCli = $isCli;
        $this->startTime = microtime(true);
    }

    public function info(string $msg): void { $this->log('INFO', $msg); }
    public function ok(string $msg): void { $this->log('OK', $msg); }
    public function warn(string $msg): void { $this->log('WARN', $msg); }
    public function dryRun(string $msg): void { $this->log('DRY-RUN', $msg); }
    public function error(string $msg): void { $this->log('ERROR', $msg); }

    private function log(string $level, string $msg): void {
        $time = date('H:i:s');
        $this->logs[] = ['time' => $time, 'level' => $level, 'message' => $msg];
        if ($this->isCli) {
            $tag = match ($level) {
                'OK' => "\033[32m[OK]\033[0m",
                'INFO' => "\033[36m[INFO]\033[0m",
                'WARN' => "\033[33m[WARN]\033[0m",
                'DRY-RUN' => "\033[35m[DRY-RUN]\033[0m",
                'ERROR' => "\033[31m[ERROR]\033[0m",
                default => "[$level]"
            };
            echo sprintf("[%s] %-19s %s\n", $time, $tag, $msg);
        }
    }

    public function getLogs(): array { return $this->logs; }
    public function getElapsedTime(): float { return round(microtime(true) - $this->startTime, 4); }
}

$logger = new MigrationLogger($isCli);

// Unit Resolver Class
class UnitResolver {
    private PDO $pdo;
    private int $warehouseId;
    private array $unitCache = [];
    private ?int $nextCodeNumber = null;
    private int $createdCount = 0;
    private MigrationLogger $logger;

    public function __construct(PDO $pdo, int $warehouseId, MigrationLogger $logger) {
        $this->pdo = $pdo;
        $this->warehouseId = $warehouseId;
        $this->logger = $logger;
    }

    public function resolve(string $rawUnit): int {
        $name = trim($rawUnit);
        if ($name === '') {
            $name = 'ชิ้น';
        }

        if (isset($this->unitCache[$name])) {
            return $this->unitCache[$name];
        }

        // Check existing unit in inv_units
        $stmt = $this->pdo->prepare("SELECT id, code FROM inv_units WHERE name = ? LIMIT 1");
        $stmt->execute([$name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $unitId = (int)$row['id'];
            $this->unitCache[$name] = $unitId;
            return $unitId;
        }

        // Determine next unit code (U006, U007, ...)
        if ($this->nextCodeNumber === null) {
            $stmtMax = $this->pdo->query("SELECT MAX(CAST(SUBSTRING(code, 2) AS UNSIGNED)) AS max_num FROM inv_units WHERE code LIKE 'U%'");
            $maxRow = $stmtMax->fetch(PDO::FETCH_ASSOC);
            $this->nextCodeNumber = ($maxRow && $maxRow['max_num'] !== null) ? (int)$maxRow['max_num'] : 0;
        }
        $this->nextCodeNumber++;
        $newCode = sprintf('U%03d', $this->nextCodeNumber);

        $stmtInsert = $this->pdo->prepare("INSERT INTO inv_units (warehouse_id, code, name, active) VALUES (?, ?, ?, 1)");
        $stmtInsert->execute([$this->warehouseId, $newCode, $name]);
        $newId = (int)$this->pdo->lastInsertId();
        $this->createdCount++;

        $this->logger->ok("Registered missing unit: '{$name}' -> ID {$newId} (Code: {$newCode})");
        $this->unitCache[$name] = $newId;
        return $newId;
    }

    public function getCreatedCount(): int {
        return $this->createdCount;
    }

    public function getResolvedCount(): int {
        return count($this->unitCache);
    }
}

// Category Resolver Class
class CategoryResolver {
    private PDO $pdo;
    private int $warehouseId;
    private array $categoryByName = [];
    private int $tonerCatId = 2;
    private int $computerCatId = 3;

    public function __construct(PDO $pdo, int $warehouseId) {
        $this->pdo = $pdo;
        $this->warehouseId = $warehouseId;
        $this->loadCategories();
    }

    private function loadCategories(): void {
        $stmt = $this->pdo->prepare("SELECT id, code, name FROM inv_categories WHERE warehouse_id = ?");
        $stmt->execute([$this->warehouseId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $this->categoryByName[$row['name']] = (int)$row['id'];
            if ($row['name'] === 'ตลับหมึก') {
                $this->tonerCatId = (int)$row['id'];
            } elseif ($row['name'] === 'อุปกรณ์คอมพิวเตอร์') {
                $this->computerCatId = (int)$row['id'];
            }
        }
    }

    public function resolve(string $rawType): int {
        $type = trim($rawType);
        $lower = strtolower($type);

        if (in_array($lower, ['toner', 'drum', 'cartridge', 'หมึก', 'ตลับหมึก', 'ดรัม'], true)) {
            return $this->tonerCatId; // 2: ตลับหมึก
        }

        return $this->computerCatId; // 3: อุปกรณ์คอมพิวเตอร์
    }

    public function getCategoryCount(): int {
        return count($this->categoryByName);
    }
}

// Dispatch Result Helpers
function dispatchSuccess(MigrationLogger $logger, array $stats, bool $isDryRun, bool $isForceFresh, bool $isCli, array $warehouse): void {
    $elapsed = $logger->getElapsedTime();
    $stats['execution_time_seconds'] = $elapsed;

    if ($isCli) {
        echo "\n" . str_repeat('=', 80) . "\n";
        echo "           IT INVENTORY MIGRATION SUMMARY (Warehouse ID 1)\n";
        echo str_repeat('=', 80) . "\n";
        echo sprintf("Status: %s | Elapsed: %.4fs | Dry-Run: %s | Force-Fresh: %s\n", 
            $isDryRun ? "\033[35mDRY-RUN SUCCESS (ROLLED BACK)\033[0m" : "\033[32mSUCCESS (COMMITTED)\033[0m",
            $elapsed,
            $isDryRun ? 'YES' : 'NO',
            $isForceFresh ? 'YES' : 'NO'
        );
        echo str_repeat('-', 80) . "\n";
        echo sprintf("%-30s | %-25s | %s\n", "Entity", "Target Table", "Count / Status");
        echo str_repeat('-', 80) . "\n";
        echo sprintf("%-30s | %-25s | %s (%s)\n", "Target Warehouse", "inv_warehouses", $warehouse['name'], $warehouse['code']);
        echo sprintf("%-30s | %-25s | %d categories mapped\n", "Categories Resolved", "inv_categories", $stats['categories_resolved']);
        echo sprintf("%-30s | %-25s | %d (%d newly registered)\n", "Units Resolved", "inv_units", $stats['units_resolved'], $stats['units_created']);
        echo sprintf("%-30s | %-25s | %d\n", "Items Migrated", "inv_items", $stats['items_migrated']);
        echo sprintf("%-30s | %-25s | %d (Lot %s)\n", "Physical Opening Lots", "inv_stock_lots", $stats['lots_created'], $stats['lot_number'] ?? 'BF-IT-69');
        echo sprintf("%-30s | %-25s | %d\n", "Stock Balances Initialized", "inv_stock_balances", $stats['balances_initialized']);
        echo sprintf("%-30s | %-25s | %d (Replayed: %d, Skipped: %d)\n", "Historical Transactions", "inv_stock_transactions", $stats['transactions_replayed'] + $stats['transactions_skipped'], $stats['transactions_replayed'], $stats['transactions_skipped']);
        $reconcileDiff = (float)($stats['reconcile_diff'] ?? 8.0);
        echo sprintf("%-30s | %-25s | %d (+%.4f units)\n", "Reconciliation Adjustments", "inv_stock_transactions", $stats['adjustments_created'], $reconcileDiff);
        echo sprintf("%-30s | %-25s | %d records (Net Ledger: %.4f)\n", "Total Audit Ledger Records", "inv_stock_transactions", $stats['total_ledger_records'], $stats['net_ledger_balance']);
        echo sprintf("%-30s | %-25s | %.4f units\n", "Final Material 1 Balance", "inv_stock_balances", $stats['final_physical_balance']);
        echo str_repeat('=', 80) . "\n";
        echo "[STATUS] All operations verified successfully. Exit code 0.\n\n";
        exit(0);
    } else {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => $isDryRun ? 'dry_run_success' : 'success',
            'dry_run' => $isDryRun,
            'force_fresh' => $isForceFresh,
            'message' => $isDryRun 
                ? 'Migration dry-run validated successfully. Database transaction was rolled back cleanly.' 
                : 'IT inventory data migration completed and committed successfully.',
            'warehouse' => $warehouse,
            'stats' => $stats,
            'logs' => $logger->getLogs()
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}

function dispatchError(MigrationLogger $logger, Throwable $e, bool $isCli): void {
    $logger->error("Migration failed: " . $e->getMessage());
    $logger->error(sprintf("Fault at %s:%d", $e->getFile(), $e->getLine()));

    if ($isCli) {
        fwrite(STDERR, "\n\033[31m[FATAL ERROR]\033[0m " . $e->getMessage() . "\n");
        fwrite(STDERR, sprintf("Exception: %s at %s:%d\n", get_class($e), $e->getFile(), $e->getLine()));
        fwrite(STDERR, "Trace:\n" . $e->getTraceAsString() . "\n");
        exit(1);
    } else {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage(),
            'error_type' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'logs' => $logger->getLogs()
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}

// Telemetry Stats Container
$stats = [
    'categories_resolved' => 0,
    'units_resolved' => 0,
    'units_created' => 0,
    'items_migrated' => 0,
    'lots_created' => 0,
    'lot_number' => 'BF-IT-69',
    'balances_initialized' => 0,
    'transactions_replayed' => 0,
    'transactions_skipped' => 0,
    'adjustments_created' => 0,
    'total_ledger_records' => 0,
    'net_ledger_balance' => 0.0,
    'final_physical_balance' => 0.0,
];

// ============================================================================
// MAIN TRANSACTION EXECUTION
// ============================================================================
try {
    $logger->info("Starting IT inventory migration pipeline...");
    if ($isDryRun) {
        $logger->dryRun("DRY-RUN MODE ENABLED: Changes will be validated and rolled back.");
    }
    if ($isForceFresh) {
        $logger->warn("FORCE-FRESH ENABLED: Existing Warehouse 1 legacy records will be cleared.");
    }

    $pdo2->beginTransaction();

    // ------------------------------------------------------------------------
    // Step 1: Warehouse Resolution & Validation
    // ------------------------------------------------------------------------
    $stmtWh = $pdo2->prepare("SELECT id, code, name FROM inv_warehouses WHERE id = 1 LIMIT 1");
    $stmtWh->execute();
    $warehouse = $stmtWh->fetch();

    if (!$warehouse) {
        throw new RuntimeException("Target Warehouse ID 1 does not exist in inv_warehouses.");
    }
    $warehouse_id = (int)$warehouse['id'];
    $logger->ok("Verified target Warehouse ID 1: '{$warehouse['name']}' ({$warehouse['code']}).");

    // ------------------------------------------------------------------------
    // Step 2: Handle --force-fresh Cleanup if requested
    // ------------------------------------------------------------------------
    if ($isForceFresh) {
        $logger->info("Purging previously migrated legacy IT records for Warehouse 1...");

        // Find existing migrated item IDs
        $stmtFindLegacyItems = $pdo2->prepare("
            SELECT id FROM inv_items 
            WHERE warehouse_id = ? 
              AND (old_code IN (SELECT code FROM mt_it_materials) OR item_code IN (SELECT code FROM mt_it_materials))
        ");
        $stmtFindLegacyItems->execute([$warehouse_id]);
        $legacyItemIds = $stmtFindLegacyItems->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($legacyItemIds)) {
            $inPlaceholders = implode(',', array_fill(0, count($legacyItemIds), '?'));

            // 1. Delete legacy transactions
            $delTx = $pdo2->prepare("DELETE FROM inv_stock_transactions WHERE warehouse_id = ? AND item_id IN ($inPlaceholders)");
            $delTx->execute(array_merge([$warehouse_id], $legacyItemIds));
            $logger->info("Deleted {$delTx->rowCount()} previous transaction records.");

            // 2. Delete legacy lots
            $delLots = $pdo2->prepare("DELETE FROM inv_stock_lots WHERE warehouse_id = ? AND item_id IN ($inPlaceholders)");
            $delLots->execute(array_merge([$warehouse_id], $legacyItemIds));
            $logger->info("Deleted {$delLots->rowCount()} previous lot records.");

            // 3. Delete legacy balances
            $delBal = $pdo2->prepare("DELETE FROM inv_stock_balances WHERE warehouse_id = ? AND item_id IN ($inPlaceholders)");
            $delBal->execute(array_merge([$warehouse_id], $legacyItemIds));
            $logger->info("Deleted {$delBal->rowCount()} previous stock balance records.");

            // 4. Delete legacy item masters
            $delItems = $pdo2->prepare("DELETE FROM inv_items WHERE warehouse_id = ? AND id IN ($inPlaceholders)");
            $delItems->execute(array_merge([$warehouse_id], $legacyItemIds));
            $logger->ok("Purged {$delItems->rowCount()} previous legacy item master records.");
        } else {
            $logger->info("No previous legacy item records found to purge.");
        }
    }

    // ------------------------------------------------------------------------
    // Step 3: Categories & Units Resolution
    // ------------------------------------------------------------------------
    $categoryResolver = new CategoryResolver($pdo2, $warehouse_id);
    $unitResolver = new UnitResolver($pdo2, $warehouse_id, $logger);

    // ------------------------------------------------------------------------
    // Step 4: Material Master Migration (mt_it_materials -> inv_items)
    // ------------------------------------------------------------------------
    $stmtOldMat = $pdo2->query("SELECT * FROM mt_it_materials ORDER BY id ASC");
    $oldMaterials = $stmtOldMat->fetchAll();

    if (empty($oldMaterials)) {
        throw new RuntimeException("No records found in source table mt_it_materials.");
    }
    $logger->info("Fetched " . count($oldMaterials) . " legacy materials from mt_it_materials.");

    // Prepared statements for Item Master
    $stmtFindItem = $pdo2->prepare("
        SELECT id FROM inv_items 
        WHERE warehouse_id = :wh AND (old_code = :old_code OR item_code = :item_code) 
        LIMIT 1
    ");

    $stmtInsertItem = $pdo2->prepare("
        INSERT INTO inv_items (
            item_code, old_code, item_name, warehouse_id, category_id, unit_id,
            purchase_unit_id, issue_unit_id, conversion_rate,
            minimum_stock, standard_price, last_purchase_price,
            active, lot_control, expiry_control, serial_control
        ) VALUES (
            :item_code, :old_code, :item_name, :warehouse_id, :category_id, :unit_id,
            :purchase_unit_id, :issue_unit_id, 1.0000,
            :minimum_stock, :standard_price, :last_purchase_price,
            :active, 0, 0, 0
        )
    ");

    $stmtUpdateItem = $pdo2->prepare("
        UPDATE inv_items SET 
            item_code = :item_code,
            old_code = :old_code,
            item_name = :item_name,
            category_id = :category_id,
            unit_id = :unit_id,
            purchase_unit_id = :purchase_unit_id,
            issue_unit_id = :issue_unit_id,
            minimum_stock = :minimum_stock,
            standard_price = :standard_price,
            last_purchase_price = :last_purchase_price,
            active = :active
        WHERE id = :id
    ");

    $material_id_map = [];      // [legacy_mat_id => target_inv_item_id]
    $material_price_map = [];   // [legacy_mat_id => price]
    $material_balance_map = []; // [legacy_mat_id => balance]

    foreach ($oldMaterials as $m) {
        $legacyMatId = (int)$m['id'];
        $material_balance_map[$legacyMatId] = (float)$m['balance'];
        $code = trim($m['code']);
        $name = trim($m['name']);
        $rawType = trim((string)$m['type']);
        $rawUnit = trim((string)$m['unit']);
        $price = (float)$m['price_per_unit'];
        $minAlert = ($m['min_alert'] !== null) ? (int)$m['min_alert'] : 0;
        $isActive = ($m['is_active'] !== null) ? (int)$m['is_active'] : 1;

        $categoryId = $categoryResolver->resolve($rawType);
        $unitId = $unitResolver->resolve($rawUnit);

        $material_price_map[$legacyMatId] = $price;

        // Check if item exists in Warehouse 1
        $stmtFindItem->execute([
            ':wh' => $warehouse_id,
            ':old_code' => $code,
            ':item_code' => $code
        ]);
        $existingItemId = $stmtFindItem->fetchColumn();

        if ($existingItemId) {
            $itemId = (int)$existingItemId;
            $stmtUpdateItem->execute([
                ':id' => $itemId,
                ':item_code' => $code,
                ':old_code' => $code,
                ':item_name' => $name,
                ':category_id' => $categoryId,
                ':unit_id' => $unitId,
                ':purchase_unit_id' => $unitId,
                ':issue_unit_id' => $unitId,
                ':minimum_stock' => $minAlert,
                ':standard_price' => $price,
                ':last_purchase_price' => $price,
                ':active' => $isActive
            ]);
            $logger->info("Updated existing item: {$code} (ID: {$itemId})");
        } else {
            $stmtInsertItem->execute([
                ':item_code' => $code,
                ':old_code' => $code,
                ':item_name' => $name,
                ':warehouse_id' => $warehouse_id,
                ':category_id' => $categoryId,
                ':unit_id' => $unitId,
                ':purchase_unit_id' => $unitId,
                ':issue_unit_id' => $unitId,
                ':minimum_stock' => $minAlert,
                ':standard_price' => $price,
                ':last_purchase_price' => $price,
                ':active' => $isActive
            ]);
            $itemId = (int)$pdo2->lastInsertId();
            $logger->ok("Created item master: {$code} (ID: {$itemId}, Name: '{$name}')");
        }

        $material_id_map[$legacyMatId] = $itemId;
        $stats['items_migrated']++;
    }

    $stats['categories_resolved'] = $categoryResolver->getCategoryCount();
    $stats['units_resolved'] = $unitResolver->getResolvedCount();
    $stats['units_created'] = $unitResolver->getCreatedCount();

    // ------------------------------------------------------------------------
    // Step 5: Lot Allocation (mt_it_lots -> inv_stock_lots)
    // ------------------------------------------------------------------------
    $stmtFindLot = $pdo2->prepare("
        SELECT id FROM inv_stock_lots 
        WHERE warehouse_id = :warehouse_id AND item_id = :item_id AND lot_number = :lot_number 
        LIMIT 1
    ");

    $stmtInsertLot = $pdo2->prepare("
        INSERT INTO inv_stock_lots (warehouse_id, item_id, lot_number, expiry_date, unit_cost, receive_qty, balance_qty)
        VALUES (:warehouse_id, :item_id, :lot_number, NULL, :unit_cost, :receive_qty, :balance_qty)
    ");

    $stmtUpdateLot = $pdo2->prepare("
        UPDATE inv_stock_lots 
        SET unit_cost = :unit_cost, receive_qty = :receive_qty, balance_qty = :balance_qty 
        WHERE id = :id
    ");

    $item_lot_map = [];   // [target_item_id => target_lot_id]
    $legacy_lot_map = []; // [legacy_lot_id => target_lot_id]
    $stmtOldLots = $pdo2->query("SELECT * FROM mt_it_lots ORDER BY id ASC");
    $oldLots = $stmtOldLots->fetchAll();

    foreach ($oldLots as $lotRow) {
        $legacyLotId = (int)$lotRow['id'];
        $legacyMatId = (int)$lotRow['material_id'];
        if (!isset($material_id_map[$legacyMatId])) {
            continue;
        }
        $targetItemId = $material_id_map[$legacyMatId];
        $lotNumber = trim($lotRow['lot_number']);
        $unitCost = (float)$lotRow['price_per_unit'];
        $receiveQty = (float)$lotRow['original_qty'];
        $balanceQty = (float)$lotRow['remaining_qty'];

        $stmtFindLot->execute([
            ':warehouse_id' => $warehouse_id,
            ':item_id' => $targetItemId,
            ':lot_number' => $lotNumber
        ]);
        $existingLotId = $stmtFindLot->fetchColumn();

        if ($existingLotId) {
            $lotId = (int)$existingLotId;
            $stmtUpdateLot->execute([
                ':id' => $lotId,
                ':unit_cost' => $unitCost,
                ':receive_qty' => $receiveQty,
                ':balance_qty' => $balanceQty
            ]);
            $logger->info("Updated existing lot '{$lotNumber}' for item ID {$targetItemId} (ID: {$lotId})");
        } else {
            $stmtInsertLot->execute([
                ':warehouse_id' => $warehouse_id,
                ':item_id' => $targetItemId,
                ':lot_number' => $lotNumber,
                ':unit_cost' => $unitCost,
                ':receive_qty' => $receiveQty,
                ':balance_qty' => $balanceQty
            ]);
            $lotId = (int)$pdo2->lastInsertId();
            $stats['lots_created']++;
            $logger->ok("Created lot '{$lotNumber}' for item ID {$targetItemId} (Unit Cost: {$unitCost}, Bal: {$balanceQty})");
        }

        $item_lot_map[$targetItemId] = $lotId;
        $legacy_lot_map[$legacyLotId] = $lotId;
        $stats['lot_number'] = $lotNumber;
    }

    // ------------------------------------------------------------------------
    // Step 6: Warehouse Stock Balances (inv_stock_balances)
    // ------------------------------------------------------------------------
    $stmtUpsertBal = $pdo2->prepare("
        INSERT INTO inv_stock_balances (warehouse_id, item_id, balance_qty)
        VALUES (:warehouse_id, :item_id, :balance_qty)
        ON DUPLICATE KEY UPDATE balance_qty = VALUES(balance_qty)
    ");

    foreach ($oldMaterials as $m) {
        $legacyMatId = (int)$m['id'];
        if (!isset($material_id_map[$legacyMatId])) {
            continue;
        }
        $targetItemId = $material_id_map[$legacyMatId];
        $balanceQty = (float)$m['balance']; // 12.0000 for item 1, 0.0000 for items 2-16

        $stmtUpsertBal->execute([
            ':warehouse_id' => $warehouse_id,
            ':item_id' => $targetItemId,
            ':balance_qty' => $balanceQty
        ]);
        $stats['balances_initialized']++;

        if ($legacyMatId === 1) {
            $stats['final_physical_balance'] = $balanceQty;
        }
    }
    $logger->ok("Initialized stock balances for all {$stats['balances_initialized']} items in Warehouse 1.");

    // ------------------------------------------------------------------------
    // Step 7: Historical Transactions Replay (mt_it_transactions)
    // ------------------------------------------------------------------------
    $stmtCheckTx = $pdo2->prepare("
        SELECT id FROM inv_stock_transactions 
        WHERE warehouse_id = :warehouse_id AND item_id = :item_id AND document_id = :legacy_tx_id 
        LIMIT 1
    ");

    $stmtInsertTx = $pdo2->prepare("
        INSERT INTO inv_stock_transactions (
            warehouse_id, item_id, lot_id, document_type, document_id, transaction_type,
            qty_in, qty_out, unit_cost, balance_qty, transaction_date, user_id, user_name, remark
        ) VALUES (
            :warehouse_id, :item_id, :lot_id, :document_type, :document_id, :transaction_type,
            :qty_in, :qty_out, :unit_cost, :balance_qty, :transaction_date, NULL, :user_name, :remark
        )
    ");

    $tx_to_legacy_lot_map = [];
    $stmtTxLots = $pdo2->query("SELECT transaction_id, lot_id FROM mt_it_transaction_lots");
    while ($tlRow = $stmtTxLots->fetch()) {
        $tx_to_legacy_lot_map[(int)$tlRow['transaction_id']] = (int)$tlRow['lot_id'];
    }

    $running_balances = []; // [target_item_id => float]
    $stmtAllTx = $pdo2->query("SELECT * FROM mt_it_transactions ORDER BY id ASC");
    $allTransactions = $stmtAllTx->fetchAll();

    foreach ($allTransactions as $tx) {
        $legacyTxId = (int)$tx['id'];
        $legacyMatId = (int)$tx['material_id'];

        if (!isset($material_id_map[$legacyMatId])) {
            continue;
        }
        $targetItemId = $material_id_map[$legacyMatId];

        if (!isset($running_balances[$targetItemId])) {
            $running_balances[$targetItemId] = 0.0000;
        }

        $qty = (float)$tx['quantity'];
        $actionType = strtoupper(trim($tx['action_type']));

        if ($actionType === 'IN') {
            $docType = 'RECEIVE';
            $qtyIn = $qty;
            $qtyOut = 0.0000;
            $running_balances[$targetItemId] += $qty;
        } else {
            $docType = 'ISSUE';
            $qtyIn = 0.0000;
            $qtyOut = $qty;
            $running_balances[$targetItemId] -= $qty;
        }
        $runningBal = $running_balances[$targetItemId];

        // Compute unit cost
        $unitCost = 0.00;
        if ((float)$tx['total_price'] > 0 && $qty > 0) {
            $unitCost = round((float)$tx['total_price'] / $qty, 2);
        } elseif (isset($material_price_map[$legacyMatId])) {
            $unitCost = (float)$material_price_map[$legacyMatId];
        }

        // Link lot_id dynamically from mt_it_transaction_lots or legacy opening receipt
        $txLotId = null;
        if (isset($tx_to_legacy_lot_map[$legacyTxId])) {
            $legacyLotId = $tx_to_legacy_lot_map[$legacyTxId];
            $txLotId = $legacy_lot_map[$legacyLotId] ?? ($item_lot_map[$targetItemId] ?? null);
        } elseif ($legacyTxId === 89 && isset($item_lot_map[$targetItemId])) {
            $txLotId = $item_lot_map[$targetItemId];
        }

        // Clean remark
        $ref = trim((string)($tx['reference_dest'] ?? ''));
        $receiver = trim((string)($tx['receiver_name'] ?? ''));
        $note = trim((string)($tx['note'] ?? ''));
        $detail = trim($ref . ($receiver !== '' ? ' ' . $receiver : '') . ($note !== '' ? ' ' . $note : ''));
        $remark = "[LegacyTX#{$legacyTxId}]" . ($detail !== '' ? ' ' . $detail : '');

        $txDate = !empty($tx['action_date']) ? $tx['action_date'] : date('Y-m-d H:i:s');
        $userName = !empty($tx['user_profile_name']) ? trim($tx['user_profile_name']) : 'System Migration';

        // Check if already replayed (idempotency)
        $stmtCheckTx->execute([
            ':warehouse_id' => $warehouse_id,
            ':item_id' => $targetItemId,
            ':legacy_tx_id' => $legacyTxId
        ]);
        if ($stmtCheckTx->fetchColumn()) {
            $stats['transactions_skipped']++;
            continue;
        }

        $stmtInsertTx->execute([
            ':warehouse_id' => $warehouse_id,
            ':item_id' => $targetItemId,
            ':lot_id' => $txLotId,
            ':document_type' => $docType,
            ':document_id' => $legacyTxId,
            ':transaction_type' => $actionType,
            ':qty_in' => $qtyIn,
            ':qty_out' => $qtyOut,
            ':unit_cost' => $unitCost,
            ':balance_qty' => $runningBal,
            ':transaction_date' => $txDate,
            ':user_name' => $userName,
            ':remark' => $remark
        ]);
        $stats['transactions_replayed']++;
    }
    $logger->ok("Transactions processed: {$stats['transactions_replayed']} replayed, {$stats['transactions_skipped']} skipped.");

    // ------------------------------------------------------------------------
    // Step 8: Ledger Reconciliation Adjustment (+8 ADJUST for Material 1)
    // ------------------------------------------------------------------------
    if (isset($material_id_map[1])) {
        $targetItemId = $material_id_map[1];
        $currentRunningBal = $running_balances[$targetItemId] ?? 0.0000;
        $physicalBal = (float)($material_balance_map[1] ?? 0.0000);
        $diff = $physicalBal - $currentRunningBal; // Dynamically calculated difference
        $stats['reconcile_diff'] = $diff;

        if (abs($diff) > 0.0001) {
            $stmtChkAdj = $pdo2->prepare("
                SELECT id FROM inv_stock_transactions 
                WHERE warehouse_id = :warehouse_id 
                  AND item_id = :item_id 
                  AND document_type = 'ADJUST' 
                  AND (remark LIKE '[RECONCILE]%' OR remark LIKE '%[LegacyReconcile]%')
                LIMIT 1
            ");
            $stmtChkAdj->execute([
                ':warehouse_id' => $warehouse_id,
                ':item_id' => $targetItemId
            ]);
            $existingAdjId = $stmtChkAdj->fetchColumn();

            if (!$existingAdjId) {
                $lotId = $item_lot_map[$targetItemId] ?? null;
                $adjDocType = 'ADJUST';
                $adjTxType = $diff > 0 ? 'IN' : 'OUT';
                $qtyIn = $diff > 0 ? $diff : 0.0000;
                $qtyOut = $diff < 0 ? abs($diff) : 0.0000;
                $unitCost = 1284.00;
                $balanceQty = $physicalBal;
                $txDate = '2026-09-30 23:59:59';
                $userName = 'System Migration';
                $remark = "[RECONCILE] ปรับปรุงยอดยกมาต้นงวดให้ตรงกับยอดคงเหลือจริง ({$physicalBal} หน่วย)";

                $stmtInsertTx->execute([
                    ':warehouse_id' => $warehouse_id,
                    ':item_id' => $targetItemId,
                    ':lot_id' => $lotId,
                    ':document_type' => $adjDocType,
                    ':document_id' => null,
                    ':transaction_type' => $adjTxType,
                    ':qty_in' => $qtyIn,
                    ':qty_out' => $qtyOut,
                    ':unit_cost' => $unitCost,
                    ':balance_qty' => $balanceQty,
                    ':transaction_date' => $txDate,
                    ':user_name' => $userName,
                    ':remark' => $remark
                ]);
                $stats['adjustments_created']++;
                $logger->ok("Inserted reconciliation ADJUST transaction: Item ID {$targetItemId}, Diff: +{$diff}, Bal: {$physicalBal}");
            } else {
                $logger->info("Reconciliation ADJUST transaction already present for Item ID {$targetItemId}.");
            }
        }
    }

    // Calculate total ledger records & net ledger balance
    $stmtTxTotals = $pdo2->prepare("
        SELECT COUNT(*) AS total_c, SUM(qty_in - qty_out) AS net_bal 
        FROM inv_stock_transactions 
        WHERE warehouse_id = :wh AND (document_id IS NOT NULL OR document_type = 'ADJUST')
    ");
    $stmtTxTotals->execute([':wh' => $warehouse_id]);
    $txTotals = $stmtTxTotals->fetch();
    $stats['total_ledger_records'] = (int)($txTotals['total_c'] ?? 0);
    $stats['net_ledger_balance'] = (float)($txTotals['net_bal'] ?? 0.0);

    // ------------------------------------------------------------------------
    // Step 9: In-Transaction Integrity Assertions (Dynamic Integrity Engine)
    // ------------------------------------------------------------------------
    // 1. Verify Migrated Items Count matches Source mt_it_materials
    $stmtVerifyItems = $pdo2->prepare("
        SELECT COUNT(*) FROM inv_items 
        WHERE warehouse_id = :wh AND old_code IS NOT NULL AND old_code != ''
    ");
    $stmtVerifyItems->execute([':wh' => $warehouse_id]);
    $migratedItemCount = (int)$stmtVerifyItems->fetchColumn();
    $expectedItemCount = count($oldMaterials);
    if ($migratedItemCount !== $expectedItemCount) {
        throw new RuntimeException("Assertion failed: Expected {$expectedItemCount} migrated items in inv_items, found {$migratedItemCount}.");
    }

    // 2. Verify Lot BF-IT-69 (dynamically compare against mt_it_lots.remaining_qty)
    $stmtVerifyLot = $pdo2->prepare("
        SELECT COUNT(*), SUM(balance_qty) FROM inv_stock_lots 
        WHERE warehouse_id = :wh AND lot_number = 'BF-IT-69'
    ");
    $stmtVerifyLot->execute([':wh' => $warehouse_id]);
    $lotData = $stmtVerifyLot->fetch(PDO::FETCH_NUM);
    $lotCount = (int)($lotData[0] ?? 0);
    $lotBalSum = (float)($lotData[1] ?? 0);

    $stmtExpectedLot = $pdo2->query("
        SELECT remaining_qty FROM mt_it_lots WHERE lot_number = 'BF-IT-69' LIMIT 1
    ");
    $expectedLotBal = (float)($stmtExpectedLot->fetchColumn() ?? 0.0);

    if ($lotCount !== 1 || abs($lotBalSum - $expectedLotBal) > 0.0001) {
        throw new RuntimeException("Assertion failed: Expected 1 lot 'BF-IT-69' with balance {$expectedLotBal}, found count={$lotCount}, balance={$lotBalSum}.");
    }

    // 3. Verify Stock Balances for All Items (dynamically compare against SUM(mt_it_materials.balance))
    $stmtVerifyBal = $pdo2->prepare("
        SELECT COUNT(*), SUM(b.balance_qty) 
        FROM inv_stock_balances b
        JOIN inv_items i ON b.item_id = i.id
        WHERE b.warehouse_id = :wh AND i.old_code IS NOT NULL AND i.old_code != ''
    ");
    $stmtVerifyBal->execute([':wh' => $warehouse_id]);
    $balData = $stmtVerifyBal->fetch(PDO::FETCH_NUM);
    $balCount = (int)($balData[0] ?? 0);
    $balSum = (float)($balData[1] ?? 0);

    $expectedTotalPhysical = (float)$pdo2->query("SELECT SUM(balance) FROM mt_it_materials")->fetchColumn();

    if ($balCount !== $expectedItemCount || abs($balSum - $expectedTotalPhysical) > 0.0001) {
        throw new RuntimeException("Assertion failed: Expected {$expectedItemCount} balance records with sum {$expectedTotalPhysical}, found count={$balCount}, sum={$balSum}.");
    }

    // 4. Verify Transactions and Net Ledger Sum = total physical balance
    $expectedLegacyTxCount = (int)$pdo2->query("SELECT COUNT(*) FROM mt_it_transactions")->fetchColumn();
    $stmtCountAdj = $pdo2->prepare("
        SELECT COUNT(*) FROM inv_stock_transactions 
        WHERE warehouse_id = :wh AND document_type = 'ADJUST'
    ");
    $stmtCountAdj->execute([':wh' => $warehouse_id]);
    $adjCount = (int)$stmtCountAdj->fetchColumn();
    $expectedTotalTxCount = $expectedLegacyTxCount + $adjCount;

    if ($stats['total_ledger_records'] !== $expectedTotalTxCount || abs($stats['net_ledger_balance'] - $expectedTotalPhysical) > 0.0001) {
        throw new RuntimeException("Assertion failed: Expected {$expectedTotalTxCount} transactions with net sum {$expectedTotalPhysical}, found {$stats['total_ledger_records']} records with net sum {$stats['net_ledger_balance']}.");
    }

    $logger->ok("All integrity assertions passed successfully ({$migratedItemCount} items, 1 lot [bal: {$expectedLotBal}], {$balCount} balances [sum: {$expectedTotalPhysical}], {$stats['total_ledger_records']} ledger transactions [net: {$stats['net_ledger_balance']}]).");

    // ------------------------------------------------------------------------
    // Step 10: Commit or Dry-Run Rollback
    // ------------------------------------------------------------------------
    if ($isDryRun) {
        $pdo2->rollBack();
        $logger->dryRun("Validation complete. Database transaction was rolled back (DRY-RUN).");
        dispatchSuccess($logger, $stats, true, $isForceFresh, $isCli, $warehouse);
    } else {
        $pdo2->commit();
        $logger->ok("Database transaction successfully committed.");
        dispatchSuccess($logger, $stats, false, $isForceFresh, $isCli, $warehouse);
    }

} catch (Throwable $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    dispatchError($logger, $e, $isCli);
}
