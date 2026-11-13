# IT Inventory Migration & Operations Manual
## Standard Operating Procedures for Warehouse ID 1 (คลังวัสดุคอมพิวเตอร์)

**Document Version**: 2.0.0  
**Target System**: Unified Multi-Warehouse Inventory System (`inv_*`)  
**Target Warehouse**: Warehouse ID 1 (`01` — `คลังวัสดุคอมพิวเตอร์`)  
**Database**: MariaDB 10.4+ / MySQL 8.0+ (`digital` on `192.168.3.3:3306`)  
**Pipeline Engine**: `backend/api-digital/inventory/migrate_it_data.php`  
**Execution Modes**: CLI & Web HTTP REST API  
**Last Updated**: 2026-11-06  

---

## Table of Contents

1. [Executive Summary & Architecture Overview](#1-executive-summary--architecture-overview)
2. [Prerequisites & Operational Readiness](#2-prerequisites--operational-readiness)
3. [Phase 1: Pre-Migration Backup Procedures](#3-phase-1-pre-migration-backup-procedures)
4. [Phase 2: Step-by-Step Execution Guide](#4-phase-2-step-by-step-execution-guide)
5. [Phase 3: Post-Migration Data Verification](#5-phase-3-post-migration-data-verification)
6. [Phase 4: Rollback Strategies & Disaster Recovery](#6-phase-4-rollback-strategies--disaster-recovery)
7. [Phase 5: Operational Reconciliation & Ongoing Management (SOPs)](#7-phase-5-operational-reconciliation--ongoing-management-sops)
8. [Troubleshooting & Frequently Asked Questions (FAQ)](#8-troubleshooting--frequently-asked-questions-faq)

---

## 1. Executive Summary & Architecture Overview

### 1.1 Purpose & Scope
This guide provides the authoritative operational manual for migrating legacy IT materials, opening stock lots, and historical transactions from the legacy schema (`mt_it_*`) into the unified multi-warehouse inventory management schema (`inv_*`) for **Warehouse ID 1 (`คลังวัสดุคอมพิวเตอร์`)**.

The migration consolidates disparate legacy records into a modern, auditable double-entry inventory ledger while guaranteeing:
- **Zero Loss of Audit History**: All historical transactions (receipts and issues) are migrated and replayed in chronological order.
- **100% Preservation of Native Catalog**: Pre-existing native catalog items (`C001` through `C014`) are completely untouched and isolated from legacy data.
- **The Golden Triad Invariant**: Complete mathematical parity across all three architectural layers:
  $$\mathbf{\text{inv\_stock\_balances}} \equiv \sum \mathbf{\text{inv\_stock\_lots}} \equiv \sum \left( \mathbf{\text{qty\_in}} - \mathbf{\text{qty\_out}} \right)$$

### 1.2 Target Architectural Schema
The target unified inventory system uses seven core relational tables:

```
[inv_warehouses] (Warehouse ID 1: คลังวัสดุคอมพิวเตอร์)
       │
       ├─► [inv_categories] (Category ID 2: ตลับหมึก, Category ID 3: อุปกรณ์คอมพิวเตอร์)
       │
       ├─► [inv_units] (U002 กล่อง, U003 อัน, U006 บอร์ด, U007 จอ)
       │
       └─► [inv_items] (16 Migrated Items + 14 Native C001-C014 Items)
              │
              ├─► [inv_stock_lots] (Physical lot tracking: Lot BF-IT-69)
              │          │
              │          ├─► [inv_stock_transactions] (Double-entry audit ledger)
              │          │
              └──────────┴─► [inv_stock_balances] (Aggregate inventory cache)
```

| Source Table (`mt_it_*`) | Target Table (`inv_*`) | Migration Mapping Rationale |
| :--- | :--- | :--- |
| `mt_it_materials` | `inv_items` | Item master catalog bound to `warehouse_id = 1`. Old code tracked in `old_code`. |
| `mt_it_categories` | `inv_categories` | Legacy types mapped to warehouse-scoped category IDs (`ตลับหมึก`, `อุปกรณ์คอมพิวเตอร์`). |
| (Text column `unit`) | `inv_units` | Units normalized; missing units (`บอร์ด`, `จอ`) dynamically registered (`U006`, `U007`). |
| `mt_it_lots` | `inv_stock_lots` | Physical lot tracking (`BF-IT-69`, cost 1,284.00 THB, balance tracking). |
| `mt_it_materials.balance` | `inv_stock_balances` | Real-time aggregate warehouse stock cache per `(warehouse_id, item_id)`. |
| `mt_it_transactions` | `inv_stock_transactions` | Sequential audit ledger with running balance recalculation per item. |
| (Audit discrepancy) | `inv_stock_transactions` | Explicit `ADJUST` transaction (+8.0000 units) to reconcile historical baseline. |

---

## 2. Prerequisites & Operational Readiness

Before executing the migration pipeline in either dry-run simulation or production live mode, verify infrastructure, runtime, network, and permission readiness.

### 2.1 System Runtime & PHP Requirements

| Requirement | Minimum Specification | Production Specification | Verification Command |
| :--- | :--- | :--- | :--- |
| **PHP Version** | `>= 8.1.0` | `8.2.x` or higher | `php -v` |
| **SAPI Compatibility** | CLI and Web (Apache/FPM) | CLI for scheduled cutover | `php -r "echo PHP_SAPI;"` |
| **Required Extensions** | `pdo_mysql`, `json`, `mbstring`, `PDO` | All bundled in standard PHP/XAMPP | `php -m` |
| **Memory Limit** | `>= 128M` | `256M` | `php -i \| findstr memory_limit` |
| **Max Execution Time** | CLI: Unlimited (`0`) / Web: `>= 60s` | CLI: Unlimited | Default CLI setting |

#### Automated PHP Extension Verification
Run this command from the project root:
```powershell
php -r "
`$req = ['pdo_mysql', 'json', 'mbstring', 'PDO'];
`$missing = array_diff(`$req, get_loaded_extensions());
if (!empty(`$missing)) {
    echo '[FAIL] Missing required PHP extensions: ' . implode(', ', `$missing) . PHP_EOL;
    exit(1);
}
echo '[PASS] All required PHP extensions are present.' . PHP_EOL;
"
```

### 2.2 Database Connectivity & Network Topology
The migration script connects directly to MariaDB database `digital` via `$pdo2` configured in `backend/config.php`.

- **Host**: `192.168.3.3`
- **Port**: `3306` (TCP)
- **Database**: `digital`
- **Character Set**: `utf8mb4`
- **Collation**: `utf8mb4_unicode_ci` / `utf8mb4_general_ci`
- **Storage Engine**: InnoDB (mandatory for atomic transactions and row-level locking)

#### Network Connectivity Tests
1. **TCP Port Connectivity (PowerShell)**:
   ```powershell
   Test-NetConnection -ComputerName 192.168.3.3 -Port 3306
   ```
   *Expected Output*: `TcpTestSucceeded : True`

2. **Database Handshake Ping**:
   ```powershell
   php -r "require 'backend/config.php'; echo (`$pdo2 instanceof PDO) ? '[PASS] Connected to MariaDB digital.' . PHP_EOL : '[FAIL] Connection failed.' . PHP_EOL;"
   ```

### 2.3 Working Directory & Path Guidelines
- **Project Root**: `c:\xampp\htdocs\vue-app\vite-digital`
- **Script Path**: `backend/api-digital/inventory/migrate_it_data.php`
- **Config Inclusion**: The script uses `require_once __DIR__ . '/../../config.php'`, allowing execution from any directory without path resolution failures.

### 2.4 Database Privilege Matrix
The database user (`digital`) must possess the following permissions on database `digital`:
- **Read (`SELECT`)**: `mt_it_*` tables and `inv_*` tables.
- **Write (`INSERT`, `UPDATE`, `DELETE`)**: `inv_units`, `inv_items`, `inv_stock_lots`, `inv_stock_balances`, `inv_stock_transactions`.
- **Transaction Control**: `BEGIN`, `COMMIT`, `ROLLBACK`.

### 2.5 Operational Cutover Freeze Notice
> **OPERATIONAL FREEZE RECOMMENDATION**:  
> Prior to executing the live migration cutover, notify hospital departments and freeze requisition submissions in the legacy IT system (`mt_it_materials` / `mt_it_transactions`). Concurrently posting new legacy transactions during migration will alter source row counts mid-execution and trigger dynamic assertion retries.

---

## 3. Phase 1: Pre-Migration Backup Procedures

Never perform a live migration without creating an immutable, verified backup of all affected source and target tables.

### 3.1 Backup Scope (12 Tables)
The backup captures all 5 legacy source tables and all 7 unified inventory target tables:
1. `mt_it_materials`
2. `mt_it_transactions`
3. `mt_it_lots`
4. `mt_it_categories`
5. `mt_it_transaction_lots`
6. `inv_warehouses`
7. `inv_categories`
8. `inv_units`
9. `inv_items`
10. `inv_stock_lots`
11. `inv_stock_balances`
12. `inv_stock_transactions`

### 3.2 File Naming Standard
Backup files must follow the strict timestamped format:
```text
backup_it_inventory_YYYYMMDD_HHMMSS.sql
```
*Example*: `backup_it_inventory_20261106_104029.sql`

### 3.3 `mysqldump` Technical Flag Rationale
- `--single-transaction`: Creates an InnoDB consistent snapshot without table locks (`FLUSH TABLES WITH READ LOCK`). Unrelated hospital applications remain 100% online.
- `--quick`: Streams rows directly from the server without client RAM buffering.
- `--default-character-set=utf8mb4`: Guarantees zero mojibake or collation corruption for Thai text (`คลังวัสดุคอมพิวเตอร์`, `ตลับหมึก`, `บอร์ด`, `จอ`).
- `--add-drop-table`: Adds `DROP TABLE IF EXISTS` for idempotent disaster recovery restoration.

### 3.4 Production Backup Commands

#### Option A: Windows PowerShell Execution
```powershell
$DB_HOST = "192.168.3.3"
$DB_PORT = "3306"
$DB_USER = "digital"
$DB_PASS = "digital10985"
$DB_NAME = "digital"
$TIMESTAMP = Get-Date -Format "yyyyMMdd_HHmmss"
$BACKUP_DIR = "backups"
$BACKUP_FILE = "$BACKUP_DIR\backup_it_inventory_${TIMESTAMP}.sql"

if (-not (Test-Path $BACKUP_DIR)) { New-Item -ItemType Directory -Path $BACKUP_DIR | Out-Null }

Write-Host "[INFO] Backing up 12 IT inventory tables to $BACKUP_FILE..." -ForegroundColor Cyan

& "c:\xampp\mysql\bin\mysqldump.exe" `
  --host=$DB_HOST `
  --port=$DB_PORT `
  --user=$DB_USER `
  --password=$DB_PASS `
  --single-transaction `
  --quick `
  --default-character-set=utf8mb4 `
  --add-drop-table `
  $DB_NAME `
  mt_it_materials mt_it_transactions mt_it_lots mt_it_categories mt_it_transaction_lots `
  inv_warehouses inv_categories inv_units inv_items inv_stock_lots inv_stock_balances inv_stock_transactions `
  --result-file=$BACKUP_FILE

if ($LASTEXITCODE -eq 0) {
    Write-Host "[SUCCESS] Pre-migration backup complete: $BACKUP_FILE" -ForegroundColor Green
} else {
    Write-Host "[ERROR] mysqldump failed with exit code $LASTEXITCODE" -ForegroundColor Red
}
```

#### Option B: Linux / Bash Shell Execution
```bash
#!/usr/bin/env bash
set -euo pipefail

DB_HOST="192.168.3.3"
DB_PORT="3306"
DB_USER="digital"
DB_PASS="digital10985"
DB_NAME="digital"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="backups"
BACKUP_FILE="${BACKUP_DIR}/backup_it_inventory_${TIMESTAMP}.sql"

mkdir -p "$BACKUP_DIR"

mysqldump \
  --host="$DB_HOST" \
  --port="$DB_PORT" \
  --user="$DB_USER" \
  --password="$DB_PASS" \
  --single-transaction \
  --quick \
  --default-character-set=utf8mb4 \
  --add-drop-table \
  "$DB_NAME" \
  mt_it_materials mt_it_transactions mt_it_lots mt_it_categories mt_it_transaction_lots \
  inv_warehouses inv_categories inv_units inv_items inv_stock_lots inv_stock_balances inv_stock_transactions \
  > "$BACKUP_FILE"

echo "[SUCCESS] Pre-migration backup created: ${BACKUP_FILE}"
```

### 3.5 Automated 3-Point Backup Integrity Verification Check
Never proceed to migration without executing the 3-point automated verification check:
1. **Size Check**: File must exist and be non-empty (`> 10,000` bytes).
2. **EOF Marker Check**: Must terminate with `-- Dump completed on YYYY-MM-DD HH:MM:SS`. Missing EOF indicates network truncation.
3. **Table DDL Check**: All 12 tables must have `-- Table structure for table \`table_name\`` definitions.

#### PowerShell Verification Command
```powershell
$backupFiles = Get-ChildItem -Path "backups\backup_it_inventory_*.sql" -ErrorAction SilentlyContinue
if (-not $backupFiles) {
    throw "Verification Failed: No backup files matching 'backups\backup_it_inventory_*.sql' were found. Run Phase 1 backup first."
}
$BACKUP_FILE = $backupFiles | Sort-Object LastWriteTime -Descending | Select-Object -First 1 -ExpandProperty FullName
Write-Host "[INFO] Verifying backup integrity: $BACKUP_FILE" -ForegroundColor Cyan

# 1. Size Check
$size = (Get-Item $BACKUP_FILE).Length
if ($size -lt 10000) { throw "Verification Failed: File size is too small ($size bytes)." }

# 2. EOF Marker Check
$tail = Get-Content $BACKUP_FILE -Tail 5 | Select-String "Dump completed on"
if (-not $tail) { throw "Verification Failed: Truncated backup. Missing EOF marker." }

# 3. Table Presence Check (12 tables)
$requiredTables = @(
  "mt_it_materials", "mt_it_transactions", "mt_it_lots", "mt_it_categories", "mt_it_transaction_lots",
  "inv_warehouses", "inv_categories", "inv_units", "inv_items", "inv_stock_lots", "inv_stock_balances", "inv_stock_transactions"
)
$foundTables = Select-String -Path $BACKUP_FILE -Pattern '-- Table structure for table `([a-zA-Z0-9_]+)`' | ForEach-Object { $_.Matches[0].Groups[1].Value }
$missing = $requiredTables | Where-Object { $foundTables -notcontains $_ }
if ($missing.Count -gt 0) { throw "Verification Failed: Missing tables: $($missing -join ', ')" }

Write-Host "[VERIFIED] Backup is 100% valid! (Size: $size bytes, All 12 tables verified, EOF marker intact)" -ForegroundColor Green
```

---

## 4. Phase 2: Step-by-Step Execution Guide

The migration engine `backend/api-digital/inventory/migrate_it_data.php` supports both CLI and Web HTTP execution with strict option whitelisting.

```
Execution Matrix:
+-------------------+--------------------+---------------------------------------------------------------+
| CLI Flag          | HTTP Parameter     | Operational Effect                                            |
+-------------------+--------------------+---------------------------------------------------------------+
| --help, -h        | ?help=1            | Displays CLI/HTTP parameter documentation and usage syntax    |
| --dry-run         | ?dry_run=1         | Simulates full migration, runs assertions, rolls back (0 DB)  |
| (none)            | (no parameters)    | Live cutover: executes pipeline, runs assertions, commits     |
| --force-fresh     | ?force_fresh=1     | Purges previously migrated legacy records in WH 1, re-imports |
+-------------------+--------------------+---------------------------------------------------------------+
```

---

### Step 2.1: Pre-flight Check & CLI Help
Display available runtime options and verify argument parser syntax:

```powershell
php backend/api-digital/inventory/migrate_it_data.php --help
```

#### Expected Output
```text
IT Inventory Migration Tool (Warehouse ID 1)
Usage: php migrate_it_data.php [options]
Options:
  --dry-run       Validate migration without persisting changes to database
  --force-fresh   Purge previously migrated legacy records for WH 1 before re-importing
  --help, -h      Display this help guide
```
- **Exit Code**: `0`
- **Database Impact**: Zero (exits immediately prior to executing database queries).

---

### Step 2.2: Simulation / Dry-run Execution (`--dry-run`)
Always perform a dry-run prior to live execution. The migration engine executes the entire 10-step pipeline inside an active transaction (`$pdo2->beginTransaction()`), evaluates all in-transaction dynamic assertions, and rolls back cleanly (`$pdo2->rollBack()`).

```powershell
php backend/api-digital/inventory/migrate_it_data.php --dry-run
```

#### Expected Console Output
```text
[HH:MM:SS] [INFO]     Starting IT inventory migration pipeline...
[HH:MM:SS] [DRY-RUN]  DRY-RUN MODE ENABLED: Changes will be validated and rolled back.
[HH:MM:SS] [OK]       Verified target Warehouse ID 1: 'คลังวัสดุคอมพิวเตอร์' (01).
[HH:MM:SS] [INFO]     Fetched 16 legacy materials from mt_it_materials.
[HH:MM:SS] [INFO]     Updated existing item: IT-Toner (ID: 161)
...
[HH:MM:SS] [INFO]     Updated existing lot 'BF-IT-69' for item ID 161 (ID: 10)
[HH:MM:SS] [OK]       Initialized stock balances for all 16 items in Warehouse 1.
[HH:MM:SS] [OK]       Transactions processed: 0 replayed, 76 skipped.
[HH:MM:SS] [INFO]     Reconciliation ADJUST transaction already present for Item ID 161.
[HH:MM:SS] [OK]       All integrity assertions passed successfully (16 items, 1 lot [bal: 11], 16 balances [sum: 11], 77 ledger transactions [net: 11]).
[HH:MM:SS] [DRY-RUN]  Validation complete. Database transaction was rolled back (DRY-RUN).

================================================================================
           IT INVENTORY MIGRATION SUMMARY (Warehouse ID 1)
================================================================================
Status: DRY-RUN SUCCESS (ROLLED BACK) | Elapsed: 0.0811s | Dry-Run: YES | Force-Fresh: NO
--------------------------------------------------------------------------------
Entity                         | Target Table              | Count / Status
--------------------------------------------------------------------------------
Target Warehouse               | inv_warehouses            | คลังวัสดุคอมพิวเตอร์ (01)
Categories Resolved            | inv_categories            | 3 categories mapped
Units Resolved                 | inv_units                 | 4 (0 newly registered)
Items Migrated                 | inv_items                 | 16
Physical Opening Lots          | inv_stock_lots            | 0 (Lot BF-IT-69)
Stock Balances Initialized     | inv_stock_balances        | 16
Historical Transactions        | inv_stock_transactions    | 76 (Replayed: 0, Skipped: 76)
Reconciliation Adjustments     | inv_stock_transactions    | 0 (+8.0000 units)
Total Audit Ledger Records     | inv_stock_transactions    | 77 records (Net Ledger: 11.0000)
Final Material 1 Balance       | inv_stock_balances        | 11.0000 units
================================================================================
[STATUS] All operations verified successfully. Exit code 0.
```

- **Exit Code**: `0`
- **Database Persistence**: `0` rows written (transaction rolled back).

---

### Step 2.3: Live Migration Execution
Executes the migration and commits all records permanently to MariaDB `digital`.

```powershell
php backend/api-digital/inventory/migrate_it_data.php
```

#### 10-Step Internal Execution Sequence
1. **Warehouse Validation**: Verifies existence of `inv_warehouses` ID 1 (`01` — `คลังวัสดุคอมพิวเตอร์`).
2. **Category Mapping**: Maps legacy types to `inv_categories` (`ตลับหมึก` ID 2, `อุปกรณ์คอมพิวเตอร์` ID 3).
3. **Unit Normalization**: Resolves units; dynamically registers missing units (`U006` บอร์ด, `U007` จอ).
4. **Item Master Upsert**: Upserts 16 items from `mt_it_materials` into `inv_items`, populating `old_code`.
5. **Opening Lot Allocation**: Inserts/updates `BF-IT-69` in `inv_stock_lots` (unit cost 1,284.00 THB).
6. **Stock Balance Initialization**: Initializes `inv_stock_balances` records for all 16 items (unique on warehouse + item).
7. **Transaction Ledger Replay**: Replays historical transactions from `mt_it_transactions` into `inv_stock_transactions`, dynamically calculating running balances.
8. **Reconciliation Adjustment**: Evaluates ledger running balance vs physical balance and records `ADJUST` (+8.0000 units) if needed.
9. **Dynamic In-Transaction Assertions**: Evaluates 4 dynamic assertions against live table counts.
10. **Transaction Commit**: Invokes `$pdo2->commit()`.

- **Exit Code**: `0`
- **Output Status**: `Status: SUCCESS (COMMITTED)`

---

### Step 2.4: Clean Re-import / Fresh Sync (`--force-fresh`)
If legacy data was modified in the source system or a complete clean re-import is required, use `--force-fresh`:

```powershell
php backend/api-digital/inventory/migrate_it_data.php --force-fresh
```

#### Reverse Foreign-Key Cascade Behavior
Before re-importing, the script executes deletions in strict reverse foreign key order:
```text
1. DELETE FROM inv_stock_transactions WHERE warehouse_id = 1 AND item_id IN (legacy items)
2. DELETE FROM inv_stock_lots         WHERE warehouse_id = 1 AND item_id IN (legacy items)
3. DELETE FROM inv_stock_balances     WHERE warehouse_id = 1 AND item_id IN (legacy items)
4. DELETE FROM inv_items              WHERE warehouse_id = 1 AND id IN (legacy items)
```

#### Absolute Catalog Preservation Guarantee
- Generic catalog items (`C001` through `C014`) are **never touched or deleted**.
- Non-IT warehouses (e.g. Warehouse 2) are **100% unaffected**.
- Source tables (`mt_it_*`) are **strictly read-only**.

*Note: You can safely simulate a fresh re-import without persisting changes by combining flags*:
```powershell
php backend/api-digital/inventory/migrate_it_data.php --force-fresh --dry-run
```

---

### Step 2.5: Web / HTTP API Execution (Dashboard Integration)
Administrative web portals and automation tools can trigger the migration via HTTP GET requests.

> **Windows PowerShell Advisory**: In Windows PowerShell 5.1/Desktop, `curl` is an alias for `Invoke-WebRequest`. Running `curl -s ...` binds `-s` to `-SessionVariable` and may hang or fail. On Windows, explicitly invoke `curl.exe` or use the native PowerShell cmdlet `Invoke-RestMethod`.

#### 1. Discovery & Help (`?help=1`)
```powershell
# Using Windows curl.exe:
curl.exe -s "http://localhost/vue-app/vite-digital/backend/api-digital/inventory/migrate_it_data.php?help=1"

# Or using native PowerShell cmdlet:
(Invoke-RestMethod -Uri "http://localhost/vue-app/vite-digital/backend/api-digital/inventory/migrate_it_data.php?help=1") | ConvertTo-Json -Depth 5
```
*HTTP 200 Response*:
```json
{
    "status": "help",
    "description": "IT Inventory Migration Tool (Warehouse ID 1)",
    "allowed_parameters": ["dry_run", "force_fresh", "help"]
}
```

#### 2. Web Dry-run Simulation (`?dry_run=1`)
```powershell
# Using Windows curl.exe:
curl.exe -s "http://localhost/vue-app/vite-digital/backend/api-digital/inventory/migrate_it_data.php?dry_run=1"

# Or using native PowerShell cmdlet:
Invoke-RestMethod -Uri "http://localhost/vue-app/vite-digital/backend/api-digital/inventory/migrate_it_data.php?dry_run=1"
```
*HTTP 200 Response*:
```json
{
    "status": "dry_run_success",
    "dry_run": true,
    "force_fresh": false,
    "message": "Migration dry-run validated successfully. Database transaction was rolled back cleanly.",
    "warehouse": {
        "id": "1",
        "code": "01",
        "name": "คลังวัสดุคอมพิวเตอร์"
    },
    "stats": {
        "items_migrated": 16,
        "total_ledger_records": 77,
        "net_ledger_balance": 11,
        "final_physical_balance": 11
    }
}
```

#### 3. Web Live Cutover
```powershell
# Using Windows curl.exe:
curl.exe -s "http://localhost/vue-app/vite-digital/backend/api-digital/inventory/migrate_it_data.php"

# Or using native PowerShell cmdlet:
Invoke-RestMethod -Uri "http://localhost/vue-app/vite-digital/backend/api-digital/inventory/migrate_it_data.php"
```

#### 4. HTTP Error Responses
- **Invalid Parameter (`HTTP 400 Bad Request`)**:
  ```json
  {
      "status": "error",
      "message": "Unrecognized query parameter: debug",
      "allowed_parameters": ["dry_run", "force_fresh", "help"]
  }
  ```
- **Internal Assertion Failure (`HTTP 500 Internal Server Error`)**:
  ```json
  {
      "status": "error",
      "message": "Assertion failed: Expected 16 migrated items in inv_items, found 15.",
      "error_type": "RuntimeException"
  }
  ```

---

## 5. Phase 3: Post-Migration Data Verification

Verify migration accuracy using the following six SQL verification queries against MariaDB `digital`.

---

### Check 5.1: Item Master Count (16 Migrated Items, 14 Native Preserved)
Verifies that all 16 legacy items exist in `inv_items` bound to Warehouse 1, and all 14 pre-existing native catalog items (`C001`–`C014`) are intact.

```sql
SELECT 
    COUNT(*) AS total_items_wh1,
    SUM(CASE WHEN old_code IS NOT NULL AND old_code != '' THEN 1 ELSE 0 END) AS migrated_items_count,
    SUM(CASE WHEN (old_code IS NULL OR old_code = '') AND item_code LIKE 'C0%' THEN 1 ELSE 0 END) AS native_items_count,
    SUM(CASE WHEN active = 1 AND old_code IS NOT NULL AND old_code != '' THEN 1 ELSE 0 END) AS migrated_active_count,
    SUM(CASE WHEN active = 0 AND old_code IS NOT NULL AND old_code != '' THEN 1 ELSE 0 END) AS migrated_inactive_count
FROM inv_items 
WHERE warehouse_id = 1;
```

#### Expected Numeric Output
| total_items_wh1 | migrated_items_count | native_items_count | migrated_active_count | migrated_inactive_count |
|:---:|:---:|:---:|:---:|:---:|
| **30** | **16** | **14** | **8** | **8** |

- **Pass Criteria**: `migrated_items_count = 16`, `native_items_count = 14`, `total_items_wh1 = 30`.

---

### Check 5.2: Opening Lot Allocation (`BF-IT-69`)
Verifies that opening lot `BF-IT-69` is recorded with unit cost 1,284.00 THB, original receive quantity 15.0000, and remaining balance quantity matching `mt_it_lots.remaining_qty`.

```sql
SELECT 
    l.id,
    l.warehouse_id,
    w.name AS warehouse_name,
    l.item_id,
    i.item_code,
    i.item_name,
    l.lot_number,
    l.unit_cost,
    l.receive_qty,
    l.balance_qty,
    (l.balance_qty * l.unit_cost) AS total_lot_value_thb
FROM inv_stock_lots l
JOIN inv_items i ON l.item_id = i.id
JOIN inv_warehouses w ON l.warehouse_id = w.id
WHERE l.warehouse_id = 1 AND l.lot_number = 'BF-IT-69';
```

#### Expected Numeric Output
| warehouse_id | warehouse_name | item_code | item_name | lot_number | unit_cost | receive_qty | balance_qty | total_lot_value_thb |
|:---:|:---|:---|:---|:---|:---:|:---:|:---:|:---:|
| **1** | คลังวัสดุคอมพิวเตอร์ | `IT-Toner` | Toner-Samsung | `BF-IT-69` | **1284.00** | **15.0000** | **11.0000** | **14124.00** |

- **Pass Criteria**: Exactly 1 lot `BF-IT-69` exists for Warehouse 1 with `unit_cost = 1284.00`, `receive_qty = 15.0000`, and `balance_qty = mt_it_lots.remaining_qty`.

---

### Check 5.3: Warehouse Stock Balances Verification
Ensures that all 16 migrated items have an initialized record in `inv_stock_balances` with unique constraint `(warehouse_id, item_id)` satisfied, 1 positive balance, 15 zero-balance rows, and zero negative balances.

```sql
SELECT 
    COUNT(*) AS total_balance_rows,
    SUM(b.balance_qty) AS total_inventory_balance,
    SUM(CASE WHEN b.balance_qty > 0 THEN 1 ELSE 0 END) AS positive_balance_items,
    SUM(CASE WHEN b.balance_qty = 0 THEN 1 ELSE 0 END) AS zero_balance_items,
    SUM(CASE WHEN b.balance_qty < 0 THEN 1 ELSE 0 END) AS negative_balance_items
FROM inv_stock_balances b
JOIN inv_items i ON b.item_id = i.id
WHERE b.warehouse_id = 1 AND i.old_code IS NOT NULL AND i.old_code != '';
```

#### Expected Numeric Output
| total_balance_rows | total_inventory_balance | positive_balance_items | zero_balance_items | negative_balance_items |
|:---:|:---:|:---:|:---:|:---:|
| **16** | **11.0000** | **1** | **15** | **0** |

- **Pass Criteria**: `total_balance_rows = 16`, `total_inventory_balance = SUM(mt_it_materials.balance)`, `negative_balance_items = 0`.

---

### Check 5.4: Transaction Ledger Audit Verification
Verifies that the historical transaction ledger contains all replayed transactions plus 1 `ADJUST` transaction, with cumulative net ledger balance matching physical stock balance.

```sql
SELECT 
    COUNT(*) AS total_transactions,
    SUM(CASE WHEN document_type = 'RECEIVE' THEN 1 ELSE 0 END) AS count_receives,
    SUM(CASE WHEN document_type = 'ISSUE' THEN 1 ELSE 0 END) AS count_issues,
    SUM(CASE WHEN document_type = 'ADJUST' THEN 1 ELSE 0 END) AS count_adjusts,
    SUM(qty_in) AS total_qty_in,
    SUM(qty_out) AS total_qty_out,
    SUM(qty_in - qty_out) AS net_ledger_balance
FROM inv_stock_transactions
WHERE warehouse_id = 1;
```

#### Expected Numeric Output
| total_transactions | count_receives | count_issues | count_adjusts | total_qty_in | total_qty_out | net_ledger_balance |
|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| **77** | **10** | **66** | **1** | **82.0000** | **71.0000** | **11.0000** |

- **Pass Criteria**: `count_adjusts = 1`, `net_ledger_balance = SUM(mt_it_materials.balance)`.

---

### Check 5.5: Category Mappings Verification
Verifies that all 16 migrated items map to the proper categories in Warehouse 1:

```sql
SELECT 
    c.id AS category_id,
    c.code AS category_code,
    c.name AS category_name,
    COUNT(i.id) AS mapped_item_count
FROM inv_categories c
LEFT JOIN inv_items i ON c.id = i.category_id 
    AND i.warehouse_id = 1 
    AND i.old_code IS NOT NULL 
    AND i.old_code != ''
WHERE c.warehouse_id = 1
GROUP BY c.id, c.code, c.name
ORDER BY c.id ASC;
```

#### Expected Numeric Output
| category_id | category_code | category_name | mapped_item_count |
|:---:|:---:|:---|:---:|
| **2** | `C01` | **ตลับหมึก** | **9** |
| **3** | `C02` | **อุปกรณ์คอมพิวเตอร์** | **7** |
| **4** | `C03` | **อุปกรณ์เครือข่าย** | **0** |

- **Pass Criteria**: Category 2 (`ตลับหมึก`) = 9 items, Category 3 (`อุปกรณ์คอมพิวเตอร์`) = 7 items, Total = 16.

---

### Check 5.6: Dynamic Units Resolution Verification
Verifies standard unit resolution and dynamic registration of `บอร์ด` (`U006`) and `จอ` (`U007`):

```sql
SELECT 
    u.id AS unit_id,
    u.code AS unit_code,
    u.name AS unit_name,
    COUNT(i.id) AS assigned_item_count
FROM inv_units u
JOIN inv_items i ON u.id = i.unit_id 
    AND i.warehouse_id = 1 
    AND i.old_code IS NOT NULL 
    AND i.old_code != ''
GROUP BY u.id, u.code, u.name
ORDER BY u.code ASC;
```

#### Expected Numeric Output
| unit_id | unit_code | unit_name | assigned_item_count |
|:---:|:---:|:---|:---:|
| 22 | `U002` | **กล่อง** | **12** |
| 24 | `U003` | **อัน** | **2** |
| 34 | `U006` | **บอร์ด** | **1** |
| 35 | `U007` | **จอ** | **1** |

- **Pass Criteria**: `U006` (`บอร์ด`) = 1 item, `U007` (`จอ`) = 1 item, Total assigned migrated items = 16.

---

### 5.7 Automated One-Liner Verification Command
Execute this one-liner command from the application root to validate all 6 verification checks in a single automated step:

```powershell
php -r "require 'backend/config.php'; `$c = [
  'Check 1: Items (16 Migrated, 14 Native)' => (`$pdo2->query('SELECT COUNT(*) FROM inv_items WHERE warehouse_id=1 AND old_code > \'\'')->fetchColumn() == 16 && `$pdo2->query('SELECT COUNT(*) FROM inv_items WHERE warehouse_id=1 AND (old_code IS NULL OR old_code=\'\')')->fetchColumn() == 14),
  'Check 2: Opening Lot BF-IT-69 (Cost 1284, Bal matches mt_it_lots)' => (`$pdo2->query('SELECT COUNT(*), SUM(balance_qty) FROM inv_stock_lots WHERE warehouse_id=1 AND lot_number=\'BF-IT-69\' AND unit_cost=1284.00')->fetch(PDO::FETCH_NUM) == [1, `$pdo2->query('SELECT remaining_qty FROM mt_it_lots WHERE lot_number=\'BF-IT-69\'')->fetchColumn()]),
  'Check 3: Stock Balances (16 Rows, Sum matches mt_it_materials)' => (`$pdo2->query('SELECT COUNT(*), SUM(balance_qty), SUM(balance_qty>0) FROM inv_stock_balances WHERE warehouse_id=1')->fetch(PDO::FETCH_NUM) == [16, `$pdo2->query('SELECT SUM(balance) FROM mt_it_materials')->fetchColumn(), 1]),
  'Check 4: Transactions (Reconciled Net Bal matches physical stock)' => (`$pdo2->query('SELECT SUM(qty_in - qty_out) FROM inv_stock_transactions WHERE warehouse_id=1')->fetchColumn() == `$pdo2->query('SELECT SUM(balance) FROM mt_it_materials')->fetchColumn()),
  'Check 5: Categories (ตลับหมึก 9, อุปกรณ์คอม 7)' => (`$pdo2->query('SELECT c.name, COUNT(i.id) FROM inv_categories c JOIN inv_items i ON c.id=i.category_id WHERE i.warehouse_id=1 AND i.old_code > \'\' GROUP BY c.name ORDER BY c.id')->fetchAll(PDO::FETCH_KEY_PAIR) == ['ตลับหมึก' => 9, 'อุปกรณ์คอมพิวเตอร์' => 7]),
  'Check 6: Dynamic Units (U006 บอร์ด, U007 จอ)' => (`$pdo2->query('SELECT code, name FROM inv_units WHERE code IN (\'U006\',\'U007\') ORDER BY code')->fetchAll(PDO::FETCH_KEY_PAIR) == ['U006' => 'บอร์ด', 'U007' => 'จอ'])
];
`$ok = true;
echo PHP_EOL . '=======================================================' . PHP_EOL . '           POST-MIGRATION VERIFICATION SUITE' . PHP_EOL . '=======================================================' . PHP_EOL;
foreach (`$c as `$title => `$pass) {
    echo sprintf('[%s] %s' . PHP_EOL, `$pass ? 'PASS' : 'FAIL', `$title);
    if (!`$pass) `$ok = false;
}
echo '=======================================================' . PHP_EOL;
echo `$ok ? '[RESULT] ALL 6 POST-MIGRATION CHECKS PASSED (Exit 0)' . PHP_EOL . PHP_EOL : '[RESULT] ONE OR MORE CHECKS FAILED (Exit 1)' . PHP_EOL . PHP_EOL;
exit(`$ok ? 0 : 1);
"
```

*Expected Terminal Result*:
```text
=======================================================
           POST-MIGRATION VERIFICATION SUITE
=======================================================
[PASS] Check 1: Items (16 Migrated, 14 Native)
[PASS] Check 2: Opening Lot BF-IT-69 (Cost 1284, Bal matches mt_it_lots)
[PASS] Check 3: Stock Balances (16 Rows, Sum matches mt_it_materials)
[PASS] Check 4: Transactions (Reconciled Net Bal matches physical stock)
[PASS] Check 5: Categories (ตลับหมึก 9, อุปกรณ์คอม 7)
[PASS] Check 6: Dynamic Units (U006 บอร์ด, U007 จอ)
=======================================================
[RESULT] ALL 6 POST-MIGRATION CHECKS PASSED (Exit 0)
```

---

## 6. Phase 4: Rollback Strategies & Disaster Recovery

Choose the appropriate rollback strategy depending on operational circumstances:

| Strategy | When to Use | Execution Tool | Scope of Impact |
| :--- | :--- | :--- | :--- |
| **Strategy A: Selective WH 1 Rollback** | Need to remove migrated records without touching other warehouses or `C001`-`C014` | MySQL CLI / Script | Only legacy records in WH 1 |
| **Strategy B: Automated Engine Purge** | Re-running migration after source data update | CLI (`--force-fresh`) | Only legacy records in WH 1 |
| **Strategy C: Full Disaster Recovery** | Catastrophic schema failure or database corruption | `Get-Content dump.sql \| mysql` (PS) / `mysql < dump.sql` (CMD) | All 12 tables |

---

### Strategy A: Selective Warehouse 1 Rollback Script (`rollback_wh1_migration.sql`)
This script purges migrated records in strict reverse foreign key order. It guarantees that **all 14 native catalog items (`C001`–`C014`) and other warehouses remain 100% untouched**.

Save the following SQL as `rollback_wh1_migration.sql`:

```sql
-- ============================================================================
-- SELECTIVE ROLLBACK SCRIPT: IT INVENTORY MIGRATION (WAREHOUSE ID 1)
-- ============================================================================
-- Target Database : digital
-- Target Warehouse: 1 ('คลังวัสดุคอมพิวเตอร์')
-- Safety Guarantee: 100% Preserves Native Catalog Items C001 - C014
-- ============================================================================

START TRANSACTION;

-- 1. Delete Migrated Transactions from inv_stock_transactions
DELETE FROM inv_stock_transactions
WHERE warehouse_id = 1
  AND (document_id IS NOT NULL OR document_type = 'ADJUST')
  AND item_id IN (
    SELECT id FROM inv_items 
    WHERE warehouse_id = 1 
      AND (old_code IN (SELECT code FROM mt_it_materials) OR item_code IN (SELECT code FROM mt_it_materials))
  );

-- 2. Delete Migrated Opening Stock Lots from inv_stock_lots
DELETE FROM inv_stock_lots
WHERE warehouse_id = 1
  AND (
    lot_number = 'BF-IT-69'
    OR item_id IN (
      SELECT id FROM inv_items 
      WHERE warehouse_id = 1 
        AND (old_code IN (SELECT code FROM mt_it_materials) OR item_code IN (SELECT code FROM mt_it_materials))
    )
  );

-- 3. Delete Stock Balances from inv_stock_balances
DELETE FROM inv_stock_balances
WHERE warehouse_id = 1
  AND item_id IN (
    SELECT id FROM inv_items 
    WHERE warehouse_id = 1 
      AND (old_code IN (SELECT code FROM mt_it_materials) OR item_code IN (SELECT code FROM mt_it_materials))
  );

-- 4. Delete Legacy Item Masters from inv_items (NEVER deletes C001-C014)
DELETE FROM inv_items
WHERE warehouse_id = 1
  AND (old_code IN (SELECT code FROM mt_it_materials) OR item_code IN (SELECT code FROM mt_it_materials));

-- 5. In-Transaction Verification
SELECT COUNT(*) AS remaining_migrated_items FROM inv_items WHERE warehouse_id = 1 AND (old_code IN (SELECT code FROM mt_it_materials) OR item_code IN (SELECT code FROM mt_it_materials));
SELECT COUNT(*) AS preserved_native_items   FROM inv_items WHERE warehouse_id = 1 AND item_code BETWEEN 'C001' AND 'C014';

-- If remaining_migrated_items = 0 and preserved_native_items = 14:
COMMIT;
-- If any count does not match:
-- ROLLBACK;
```

#### Execution Command
```powershell
Get-Content rollback_wh1_migration.sql -Encoding UTF8 | & "c:\xampp\mysql\bin\mysql.exe" `
  --host=192.168.3.3 --port=3306 --user=digital --password=digital10985 --default-character-set=utf8mb4 digital
```

---

### Strategy B: Automated Rollback via `--force-fresh`
Instead of executing manual SQL scripts, the migration CLI includes automated reverse-FK purge functionality:

```powershell
php backend/api-digital/inventory/migrate_it_data.php --force-fresh
```
This automatically clears legacy transactions, lots, balances, and items for Warehouse 1 inside an active transaction before re-importing fresh data.

---

### Strategy C: Full Disaster Recovery Restore
In the event of unrecoverable database corruption or catastrophic failure, restore the full database snapshot taken in Phase 1:

```powershell
$BACKUP_FILE = "backups\backup_it_inventory_20261106_104029.sql" # Set to target backup file
if (-not (Test-Path $BACKUP_FILE)) {
    throw "Fatal Error: Specified backup file not found: '$BACKUP_FILE'. Please verify the filename."
}

Write-Host "[ALERT] Executing Full Disaster Recovery Restore from $BACKUP_FILE..." -ForegroundColor Yellow

Get-Content $BACKUP_FILE -Encoding UTF8 | & "c:\xampp\mysql\bin\mysql.exe" `
  --host=192.168.3.3 --port=3306 --user=digital --password=digital10985 --default-character-set=utf8mb4 digital

if ($LASTEXITCODE -eq 0) {
    Write-Host "[SUCCESS] Database restored cleanly to pre-migration state." -ForegroundColor Green
} else {
    Write-Host "[FATAL] Restore failed with code $LASTEXITCODE." -ForegroundColor Red
}
```

---

## 7. Phase 5: Operational Reconciliation & Ongoing Management (SOPs)

---

### 7.1 Historical Balance Reconciliation: Forensic Trace of the +8 Discrepancy

#### The Root Cause
A naive calculation of the net sum of transactions for Material 1 (`IT-Toner`) in `mt_it_transactions` yields:
- Total Stock In: **65 units** (TX 22 = 50, TX 89 = 15)
- Total Stock Out: **62 units** (across 57 issues)
- Mathematical Ledger Net: `65 - 62 = 3 units`
- Physical Count / Lot Balance: **11 units**
- Variance: `11 - 3 = +8 units`

#### Forensic Chronological Audit Trace
```text
[Period 1: Negative Running Balance Baseline]
- In late 2025 (TX 12-21), 10 issues were entered before any initial receipt was keyed in, creating a -10 balance.
- TX 22 received 50 units (running balance rose to +40).
- Issues TX 23-80 steadily reduced the balance to 0 on 2026-09-01.

[Period 2: The Physical Discrepancy Window]
- Between 2026-09-01 and 2026-10-08, 8 units were issued (TX 81 through TX 87).
- Staff were issuing physical cartridges that were physically present on shelves from an unrecorded delivery or previous cycle.
- The legacy system lacked stock floor constraints, allowing the raw ledger balance to drop to -8.0000.

[Period 3: Fiscal Year 2569 Opening Stock Lot]
- On 2026-09-30, staff conducted a stock take and recorded Lot BF-IT-69 with 15 units (TX 89).
- Adding 15 units to the -8 running balance produced a ledger balance of +7.0000.
- Subsequent issues TX 90 (-1), TX 91 (-2), and TX 92 (-1) reduced the lot to 11 units and the raw ledger to 3 units.
```

#### Why Explicit `ADJUST` is the Standard Accounting Solution
1. Modifying or deleting past transactions violates hospital audit compliance and erases departmental supply records.
2. Setting stock balance to 3 would falsely write off 8 physical units valued at 10,272.00 THB.
3. The migration posts an explicit audit reconciliation transaction:
   - `document_type`: `'ADJUST'`
   - `transaction_type`: `'IN'`
   - `qty_in`: `8.0000`
   - `unit_cost`: `1284.00`
   - `balance_qty`: Current physical count
   - `remark`: `'[RECONCILE] ปรับปรุงยอดยกมาต้นงวดให้ตรงกับยอดคงเหลือจริง'`

$$\begin{aligned}
\text{Raw Historical Ledger Net} & = +3.0000 \\
\text{Reconciliation Adjustment} & = +8.0000 \\
\hline
\mathbf{\text{Final Reconciled Ledger Balance}} & = \mathbf{11.0000} \\
\mathbf{\text{Warehouse Stock Balance (inv\_stock\_balances)}} & = \mathbf{11.0000} \\
\mathbf{\text{Physical Lot Balance (inv\_stock\_lots)}} & = \mathbf{11.0000}
\end{aligned}$$

---

### 7.2 SOP-INV-001: Standard Operating Procedure for Future Stock Takes & Adjustments
**Effective Date**: Post-Migration Go-Live  
**Document Type**: `ADJUST`  

#### Mandatory Integrity Principle
> **NEVER directly edit `inv_stock_balances` or `inv_stock_lots` via SQL UPDATE commands.**  
> Every adjustment must be posted as an atomic transaction in `inv_stock_transactions` with `document_type = 'ADJUST'`.

#### 5-Phase Procedure
1. **Physical Count**: Count stock using the printed count sheet (`GET /backend/api-digital/inventory/items.php?warehouse_id=1`).
2. **Calculate Variance**:
   $$\text{Variance} = \text{Physical Count} - \text{System Balance}$$
3. **Approval**:
   - Variance $< 1,000$ THB: Head of Computer Center.
   - Variance $\ge 1,000$ THB: Hospital Inventory Audit Committee.
4. **Post Adjustment**:
   - **Positive Variance ($\text{Variance} > 0$)**: Post `ADJUST` with `transaction_type = 'IN'`, increment lot and balance.
   - **Negative Variance ($\text{Variance} < 0$)**: Post `ADJUST` with `transaction_type = 'OUT'`, decrement lot and balance.
5. **Verify Continuity**: Verify that the updated running `balance_qty` matches the physical count sheet.

---

### 7.3 SOP-INV-002: Standard Operating Procedure for Regular Stock Receipts & Issues
**Effective Date**: Post-Migration Go-Live  

#### 1. Goods Receipt (`document_type = 'RECEIVE'`)
- **Endpoint**: `POST /backend/api-digital/inventory/receive.php`
- **Execution Rules**:
  - Inserts header in `inv_receipts` and line items in `inv_receipt_items`.
  - Upserts lot in `inv_stock_lots` with `lot_number`, `expiry_date`, and `unit_cost`.
  - Increments `balance_qty` in `inv_stock_balances`.
  - Inserts audit ledger row into `inv_stock_transactions` (`document_type = 'RECEIVE'`, `transaction_type = 'IN'`).

#### 2. Departmental Stock Issue (`document_type = 'ISSUE'`)
- **Endpoint**: `POST /backend/api-digital/inventory/issue.php`
- **Execution Rules**:
  - Validates stock availability using pessimistic locking (`SELECT ... FOR UPDATE`).
  - Strict FIFO/FEFO lot deduction (oldest active lot issued first).
  - Anti-negative stock safeguard: Throws error if `balance_qty < requested_qty`. Under no circumstances can stock fall below 0.
  - Decrements `balance_qty` in `inv_stock_lots` and `inv_stock_balances`.
  - Inserts audit ledger row into `inv_stock_transactions` (`document_type = 'ISSUE'`, `transaction_type = 'OUT'`).

---

## 8. Troubleshooting & Frequently Asked Questions (FAQ)

### Q1: Database connection failure (`$pdo2 is not initialized`)
- **Cause**: Database server is unreachable or credentials in `backend/.env` are incorrect.
- **Solution**:
  1. Test TCP connectivity: `Test-NetConnection -ComputerName 192.168.3.3 -Port 3306`.
  2. Verify credentials in `backend/.env` (`DB2_HOST`, `DB2_USER`, `DB2_PASS`, `DB2_NAME`).
  3. Ensure MySQL service on `192.168.3.3` is running and accepts remote connections.

### Q2: Unrecognized CLI option or HTTP parameter error
- **Cause**: Passing parameters outside the strict whitelist.
- **Solution**:
  - Allowed CLI flags: `--dry-run`, `--force-fresh`, `--help`, `-h`.
  - Allowed HTTP parameters: `dry_run`, `force_fresh`, `help`.
  - Typos like `--dryrun` or `?test=1` will trigger an immediate error.

### Q3: Dynamic assertion failure during dry-run or live execution
- **Cause**: Live transactions were posted in the legacy system while the migration script was executing, changing source balances.
- **Solution**:
  1. Enforce a maintenance freeze on the legacy system.
  2. Run `php backend/api-digital/inventory/migrate_it_data.php --dry-run` to verify current counts.
  3. Re-run live migration.

### Q4: Foreign key constraint error (`Error 1451: Cannot delete or update a parent row`)
- **Cause**: Attempting to delete items from `inv_items` before removing child records in `inv_stock_transactions` and `inv_stock_lots`.
- **Solution**:
  - Always execute deletions in strict reverse dependency order: `inv_stock_transactions` $\to$ `inv_stock_lots` $\to$ `inv_stock_balances` $\to$ `inv_items`.
  - Use `rollback_wh1_migration.sql` or `--force-fresh`, which automatically enforce this sequence.

### Q5: Thai character display appears corrupted (mojibake)
- **Cause**: Client or terminal character set mismatch.
- **Solution**:
  - Ensure MariaDB connection uses `SET NAMES utf8mb4`.
  - In PowerShell, set encoding before running commands:
    ```powershell
    [Console]::OutputEncoding = [System.Text.Encoding]::UTF8
    ```
