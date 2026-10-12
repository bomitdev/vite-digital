<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

try {

    // 1. PR Header
    $pdo2->exec("
        CREATE TABLE IF NOT EXISTS `inv_purchase_requests` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `pr_number` VARCHAR(50) NOT NULL COMMENT 'เลขที่เอกสาร PR',
            `warehouse_id` INT(11) NOT NULL,
            `request_date` DATETIME NOT NULL,
            `requester_name` VARCHAR(150) NOT NULL,
            `reason` TEXT COMMENT 'เหตุผลการขอซื้อ',
            `status` VARCHAR(50) DEFAULT 'PENDING' COMMENT 'PENDING, HEAD_APPROVED, HEAD_REJECTED, DIR_APPROVED, DIR_REJECTED',
            `head_approver_name` VARCHAR(150) DEFAULT NULL,
            `head_approve_date` DATETIME DEFAULT NULL,
            `head_reject_reason` TEXT DEFAULT NULL,
            `dir_approver_name` VARCHAR(150) DEFAULT NULL,
            `dir_approve_date` DATETIME DEFAULT NULL,
            `dir_reject_reason` TEXT DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_pr_number` (`pr_number`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. PR Items
    $pdo2->exec("
        CREATE TABLE IF NOT EXISTS `inv_purchase_request_items` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `pr_id` INT(11) NOT NULL,
            `item_id` INT(11) NOT NULL,
            `qty_requested` INT(11) NOT NULL,
            `estimated_price` DECIMAL(10,2) DEFAULT 0,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`pr_id`) REFERENCES inv_purchase_requests(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 3. PO Header
    $pdo2->exec("
        CREATE TABLE IF NOT EXISTS `inv_purchase_orders` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `pr_id` INT(11) NOT NULL,
            `po_number` VARCHAR(50) NOT NULL,
            `vendor_id` INT(11) DEFAULT NULL,
            `po_date` DATETIME NOT NULL,
            `expected_date` DATE DEFAULT NULL,
            `purchaser_name` VARCHAR(150) NOT NULL,
            `note` TEXT,
            `status` VARCHAR(50) DEFAULT 'ORDERED' COMMENT 'ORDERED, PARTIAL_RECEIVED, COMPLETED, CANCELLED',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_po_number` (`po_number`),
            FOREIGN KEY (`pr_id`) REFERENCES inv_purchase_requests(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 4. PO Items
    $pdo2->exec("
        CREATE TABLE IF NOT EXISTS `inv_purchase_order_items` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `po_id` INT(11) NOT NULL,
            `item_id` INT(11) NOT NULL,
            `qty_ordered` INT(11) NOT NULL,
            `qty_received` INT(11) DEFAULT 0,
            `unit_price` DECIMAL(10,2) NOT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`po_id`) REFERENCES inv_purchase_orders(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 5. Alter inv_receipts to link with PO (Ignore error if exists)
    try {
        $pdo2->exec("ALTER TABLE `inv_receipts` ADD COLUMN `po_id` INT(11) DEFAULT NULL AFTER `warehouse_id`");
    } catch (Exception $e) {
        // column might already exist
    }

    echo json_encode(['status' => 'success', 'message' => 'PR/PO Tables created successfully!']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
