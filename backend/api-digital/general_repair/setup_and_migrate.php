<?php
/**
 * Setup General Repair Tables & Migrate 745 Records from hosoffice to digital
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../moph_notify_helper.php';

header("Content-Type: application/json; charset=UTF-8");

$isCli = (PHP_SAPI === 'cli');

try {
    echo $isCli ? "1. Creating master tables in digital...\n" : "";

    // 1. Locations
    $pdo2->exec("
        CREATE TABLE IF NOT EXISTS `general_repair_locations` (
            `id` INT UNSIGNED NOT NULL PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Categories
    $pdo2->exec("
        CREATE TABLE IF NOT EXISTS `general_repair_categories` (
            `id` INT UNSIGNED NOT NULL PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 3. Service Types
    $pdo2->exec("
        CREATE TABLE IF NOT EXISTS `general_repair_service_types` (
            `id` INT UNSIGNED NOT NULL PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 4. Technicians
    $pdo2->exec("
        CREATE TABLE IF NOT EXISTS `general_repair_technicians` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `person_id` INT NOT NULL UNIQUE,
            `fullname` VARCHAR(150) NOT NULL,
            `position` VARCHAR(150) DEFAULT NULL,
            `phone` VARCHAR(50) DEFAULT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 5. Main Repair Requests Table
    $pdo2->exec("
        CREATE TABLE IF NOT EXISTS `general_repair_requests` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `ticket_no` VARCHAR(30) NOT NULL UNIQUE,
            `legacy_id` INT DEFAULT NULL UNIQUE,
            `repair_title` VARCHAR(255) NOT NULL,
            `symptom` TEXT DEFAULT NULL,
            `category_id` INT DEFAULT NULL,
            `category_name` VARCHAR(150) DEFAULT NULL,
            `service_type_id` INT DEFAULT NULL,
            `service_type_name` VARCHAR(150) DEFAULT NULL,
            `location_id` INT DEFAULT NULL,
            `location_name` VARCHAR(255) DEFAULT NULL,
            `priority_id` VARCHAR(10) NOT NULL DEFAULT '01',
            `priority_name` VARCHAR(50) NOT NULL DEFAULT 'ปกติ',
            `requester_id` INT DEFAULT NULL,
            `requester_name` VARCHAR(150) NOT NULL,
            `requester_phone` VARCHAR(50) DEFAULT NULL,
            `department_id` VARCHAR(20) DEFAULT NULL,
            `department_name` VARCHAR(150) DEFAULT NULL,
            `asset_id` VARCHAR(50) DEFAULT NULL,
            `technician_id` INT DEFAULT NULL,
            `technician_name` VARCHAR(150) DEFAULT NULL,
            `technician_comment` TEXT DEFAULT NULL,
            `status` VARCHAR(30) NOT NULL DEFAULT 'Pending',
            `received_at` DATETIME DEFAULT NULL,
            `completed_at` DATETIME DEFAULT NULL,
            `cancel_reason` VARCHAR(255) DEFAULT NULL,
            `image_before` VARCHAR(255) DEFAULT NULL,
            `image_after` VARCHAR(255) DEFAULT NULL,
            `satisfaction_score` TINYINT DEFAULT NULL,
            `satisfaction_comment` TEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_status` (`status`),
            INDEX `idx_created_at` (`created_at`),
            INDEX `idx_requester` (`requester_name`),
            INDEX `idx_tech` (`technician_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Ensure MOPH Notify channel 'repair' exists
    ensureMophNotifyChannelsTable($pdo2);
    $checkCh = $pdo2->prepare("SELECT id, client_key, secret_key FROM moph_notify_channels WHERE channel_code = 'repair'");
    $checkCh->execute();
    $repairChannel = $checkCh->fetch(PDO::FETCH_ASSOC);

    if (!$repairChannel) {
        // Fallback keys from admin or it channel
        $adminCreds = $pdo2->query("SELECT client_key, secret_key FROM moph_notify_channels WHERE channel_code = 'admin'")->fetch(PDO::FETCH_ASSOC);
        $initCk = $adminCreds['client_key'] ?? null;
        $initSk = $adminCreds['secret_key'] ?? null;

        $insCh = $pdo2->prepare("
            INSERT INTO moph_notify_channels 
            (channel_code, channel_name, description, notify_url, client_key, secret_key, is_active, is_system, sort_order)
            VALUES ('repair', 'งานซ่อมบำรุง / ทีมช่าง', 'แจ้งเตือนงานแจ้งซ่อมบำรุงทั่วไป สำหรับทีมช่างและผู้ดูแล', 'https://morpromt2f.moph.go.th/api/notify/send', :ck, :sk, 1, 1, 25)
        ");
        $insCh->execute([':ck' => $initCk, ':sk' => $initSk]);
        echo $isCli ? "Added 'repair' channel to moph_notify_channels.\n" : "";
    }

    echo $isCli ? "2. Syncing master data from hosoffice...\n" : "";

    // Sync Locations
    $stmtLoc = $pdo3->query("SELECT ID, LOCATION_NAME FROM repair_location_see WHERE LOCATION_NAME IS NOT NULL AND LOCATION_NAME != ''");
    $insertLoc = $pdo2->prepare("INSERT INTO general_repair_locations (id, name, is_active) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE name = VALUES(name)");
    $locCount = 0;
    while ($r = $stmtLoc->fetch(PDO::FETCH_ASSOC)) {
        $insertLoc->execute([$r['ID'], trim($r['LOCATION_NAME'])]);
        $locCount++;
    }
    echo $isCli ? "Synced {$locCount} locations.\n" : "";

    // Sync Categories
    $stmtCat = $pdo3->query("SELECT OTHER_ID, OTHER_NAME FROM repair_other WHERE OTHER_NAME IS NOT NULL AND OTHER_NAME != ''");
    $insertCat = $pdo2->prepare("INSERT INTO general_repair_categories (id, name, is_active) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE name = VALUES(name)");
    $catCount = 0;
    while ($r = $stmtCat->fetch(PDO::FETCH_ASSOC)) {
        $insertCat->execute([$r['OTHER_ID'], trim($r['OTHER_NAME'])]);
        $catCount++;
    }
    echo $isCli ? "Synced {$catCount} categories.\n" : "";

    // Sync Service Types
    $stmtServ = $pdo3->query("SELECT REPAIR_TYPE_SERVICE_ID, REPAIR_TYPE_SERVICE_NAME FROM repair_type_service WHERE REPAIR_TYPE_SERVICE_NAME IS NOT NULL AND REPAIR_TYPE_SERVICE_NAME != ''");
    $insertServ = $pdo2->prepare("INSERT INTO general_repair_service_types (id, name, is_active) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE name = VALUES(name)");
    $servCount = 0;
    while ($r = $stmtServ->fetch(PDO::FETCH_ASSOC)) {
        $insertServ->execute([$r['REPAIR_TYPE_SERVICE_ID'], trim($r['REPAIR_TYPE_SERVICE_NAME'])]);
        $servCount++;
    }
    echo $isCli ? "Synced {$servCount} service types.\n" : "";

    // Sync Technicians
    $stmtTech = $pdo3->query("
        SELECT t.PERSON_ID, t.TECH_POSITION, t.ACTIVE, p.HR_FNAME, p.HR_LNAME, p.HR_PHONE
        FROM repair_tech t
        LEFT JOIN hr_person p ON p.ID = t.PERSON_ID
    ");
    $insertTech = $pdo2->prepare("
        INSERT INTO general_repair_technicians (person_id, fullname, position, phone, is_active)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE fullname = VALUES(fullname), position = VALUES(position), phone = VALUES(phone), is_active = VALUES(is_active)
    ");
    $techCount = 0;
    while ($r = $stmtTech->fetch(PDO::FETCH_ASSOC)) {
        $name = trim(($r['HR_FNAME'] ?? '') . ' ' . ($r['HR_LNAME'] ?? ''));
        $active = (strtolower(trim((string)$r['ACTIVE'])) === 'true' || $r['ACTIVE'] == 1) ? 1 : 0;
        $insertTech->execute([
            $r['PERSON_ID'],
            $name ?: "ช่าง ID {$r['PERSON_ID']}",
            $r['TECH_POSITION'],
            $r['HR_PHONE'],
            $active
        ]);
        $techCount++;
    }
    echo $isCli ? "Synced {$techCount} technicians.\n" : "";

    echo $isCli ? "3. Migrating 745 records from hosoffice.repair_index...\n" : "";

    // Prepare uploads directory for legacy images
    $uploadDir = __DIR__ . '/../../uploads/repairs/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    $selectRepairs = $pdo3->query("
        SELECT 
            r.ID,
            r.REPAIR_ID,
            r.REPAIR_NAME,
            r.SYMPTOM,
            r.ARTICLE_ID,
            r.OTHER_ID,
            o.OTHER_NAME,
            r.LOCATION_SEE_ID,
            l.LOCATION_NAME,
            r.PRIORITY_ID,
            p.PRIORITY_NAME,
            r.USER_REQUEST_ID,
            r.USRE_REQUEST_NAME,
            r.USER_REQUEST_PHONE,
            r.DATE_TIME_REQUEST,
            r.REPAIR_DATE,
            r.REPAIR_TIME,
            r.REPAIR_STATUS,
            r.TECH_WANT_ID,
            r.TECH_WANT_NAME,
            r.DEPARTMENT_ID,
            d.HR_DEPARTMENT_NAME,
            ds.HR_DEPARTMENT_SUB_NAME,
            r.REPAIR_IMG
        FROM repair_index r
        LEFT JOIN repair_other o ON o.OTHER_ID = r.OTHER_ID
        LEFT JOIN repair_location_see l ON l.ID = r.LOCATION_SEE_ID
        LEFT JOIN repair_priority p ON p.PRIORITY_ID = r.PRIORITY_ID
        LEFT JOIN hr_department d ON d.HR_DEPARTMENT_ID = r.DEPARTMENT_ID
        LEFT JOIN hr_department_sub ds ON ds.HR_DEPARTMENT_SUB_ID = r.DEPARTMENT_SUB_ID
        ORDER BY r.ID ASC
    ");

    $insertRepair = $pdo2->prepare("
        INSERT INTO general_repair_requests (
            ticket_no, legacy_id, repair_title, symptom,
            category_id, category_name, location_id, location_name,
            priority_id, priority_name,
            requester_id, requester_name, requester_phone,
            department_id, department_name, asset_id,
            technician_id, technician_name,
            status, completed_at, cancel_reason,
            image_before, created_at
        ) VALUES (
            :ticket_no, :legacy_id, :repair_title, :symptom,
            :category_id, :category_name, :location_id, :location_name,
            :priority_id, :priority_name,
            :requester_id, :requester_name, :requester_phone,
            :department_id, :department_name, :asset_id,
            :technician_id, :technician_name,
            :status, :completed_at, :cancel_reason,
            :image_before, :created_at
        )
        ON DUPLICATE KEY UPDATE
            repair_title = VALUES(repair_title),
            symptom = VALUES(symptom),
            category_id = VALUES(category_id),
            category_name = VALUES(category_name),
            location_id = VALUES(location_id),
            location_name = VALUES(location_name),
            priority_id = VALUES(priority_id),
            priority_name = VALUES(priority_name),
            requester_name = VALUES(requester_name),
            requester_phone = VALUES(requester_phone),
            department_name = VALUES(department_name),
            technician_name = VALUES(technician_name),
            image_before = COALESCE(VALUES(image_before), image_before)
    ");

    $migrated = 0;
    $imageExtracted = 0;

    while ($row = $selectRepairs->fetch(PDO::FETCH_ASSOC)) {
        $legacyId = (int)$row['ID'];
        $createdTime = $row['DATE_TIME_REQUEST'];
        if (!$createdTime || $createdTime === '0000-00-00 00:00:00') {
            $createdTime = !empty($row['REPAIR_DATE']) ? $row['REPAIR_DATE'] . ' ' . ($row['REPAIR_TIME'] ?: '08:00:00') : date('Y-m-d H:i:s');
        }

        // Ticket number
        $ym = date('Ym', strtotime($createdTime));
        $ticketNo = "REP-{$ym}-" . str_pad($legacyId, 4, '0', STR_PAD_LEFT);

        // Status mapping
        $rawStatus = strtoupper(trim((string)$row['REPAIR_STATUS']));
        $status = 'Completed';
        $cancelReason = null;
        $completedAt = null;

        if ($rawStatus === 'CANCEL' || in_array($legacyId, [423, 434], true)) {
            $status = 'Cancelled';
            $cancelReason = 'ต้องการแจ้งซ่อมใหม่';
        } elseif ($createdTime >= '2026-11-01 00:00:00') {
            // Keep recent jobs in November 2026 active as Pending
            $status = 'Pending';
        } else {
            $status = 'Completed';
            $completedAt = $createdTime;
        }

        // Priority
        $pId = $row['PRIORITY_ID'] ?: '01';
        $pName = $row['PRIORITY_NAME'] ?: 'ปกติ';

        // Department name
        $deptName = $row['HR_DEPARTMENT_SUB_NAME'] ?: ($row['HR_DEPARTMENT_NAME'] ?: 'กลุ่มงานบริหารทั่วไป');

        // Image extraction
        $imgPath = null;
        if (!empty($row['REPAIR_IMG'])) {
            $bin = $row['REPAIR_IMG'];
            // Check if valid image binary
            if (strlen($bin) > 100) {
                $imgFilename = "repair_legacy_{$legacyId}.jpg";
                $targetPath = $uploadDir . $imgFilename;
                if (!file_exists($targetPath)) {
                    @file_put_contents($targetPath, $bin);
                }
                $imgPath = "backend/uploads/repairs/{$imgFilename}";
                $imageExtracted++;
            }
        }

        $insertRepair->execute([
            ':ticket_no' => $ticketNo,
            ':legacy_id' => $legacyId,
            ':repair_title' => $row['REPAIR_NAME'] ?: 'แจ้งซ่อมทั่วไป #' . $legacyId,
            ':symptom' => $row['SYMPTOM'],
            ':category_id' => !empty($row['OTHER_ID']) ? (int)$row['OTHER_ID'] : null,
            ':category_name' => $row['OTHER_NAME'] ?: null,
            ':location_id' => !empty($row['LOCATION_SEE_ID']) ? (int)$row['LOCATION_SEE_ID'] : null,
            ':location_name' => $row['LOCATION_NAME'] ?: null,
            ':priority_id' => $pId,
            ':priority_name' => $pName,
            ':requester_id' => !empty($row['USER_REQUEST_ID']) ? (int)$row['USER_REQUEST_ID'] : null,
            ':requester_name' => $row['USRE_REQUEST_NAME'] ?: 'ไม่ระบุผู้แจ้ง',
            ':requester_phone' => $row['USER_REQUEST_PHONE'] ?: null,
            ':department_id' => $row['DEPARTMENT_ID'] ?: null,
            ':department_name' => $deptName,
            ':asset_id' => $row['ARTICLE_ID'] ?: null,
            ':technician_id' => !empty($row['TECH_WANT_ID']) ? (int)$row['TECH_WANT_ID'] : null,
            ':technician_name' => $row['TECH_WANT_NAME'] ?: null,
            ':status' => $status,
            ':completed_at' => $completedAt,
            ':cancel_reason' => $cancelReason,
            ':image_before' => $imgPath,
            ':created_at' => $createdTime,
        ]);
        $migrated++;
    }

    $finalCount = (int)$pdo2->query("SELECT COUNT(*) FROM general_repair_requests")->fetchColumn();

    $summary = [
        'status' => 'success',
        'message' => 'Setup and migration completed successfully',
        'migrated_rows' => $migrated,
        'images_extracted' => $imageExtracted,
        'total_requests_in_digital' => $finalCount,
    ];

    if ($isCli) {
        echo "Done! Migrated {$migrated} rows (Extracted {$imageExtracted} images). Total in DB: {$finalCount}\n";
    } else {
        echo json_encode($summary);
    }
} catch (Exception $e) {
    if ($isCli) {
        echo "Error: " . $e->getMessage() . "\n";
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
