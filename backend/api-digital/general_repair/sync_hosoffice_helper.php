<?php
/**
 * Auto-sync new repair requests from hosoffice.repair_index into digital.general_repair_requests
 */

function syncHosofficeRepairs(PDO $pdo2, ?PDO $pdo3): int
{
    if (!$pdo3) {
        return 0;
    }

    try {
        $maxLegacyId = (int)$pdo2->query("SELECT COALESCE(MAX(legacy_id), 0) FROM general_repair_requests")->fetchColumn();

        $stmt = $pdo3->prepare("
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
            WHERE r.ID > ?
            ORDER BY r.ID ASC
        ");
        $stmt->execute([$maxLegacyId]);
        $newRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($newRows)) {
            return 0;
        }

        $uploadDir = __DIR__ . '/../../uploads/repairs/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        $insertRepair = $pdo2->prepare("
            INSERT IGNORE INTO general_repair_requests (
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
        ");

        $synced = 0;
        foreach ($newRows as $row) {
            $legacyId = (int)$row['ID'];
            $createdTime = $row['DATE_TIME_REQUEST'];
            if (!$createdTime || $createdTime === '0000-00-00 00:00:00') {
                $createdTime = !empty($row['REPAIR_DATE'])
                    ? $row['REPAIR_DATE'] . ' ' . ($row['REPAIR_TIME'] ?: '08:00:00')
                    : date('Y-m-d H:i:s');
            }

            $ym = date('Ym', strtotime($createdTime));
            $ticketNo = "HOS-{$ym}-" . str_pad((string)$legacyId, 4, '0', STR_PAD_LEFT);

            $rawStatus = strtoupper(trim((string)$row['REPAIR_STATUS']));
            $status = 'Pending';
            $cancelReason = null;
            $completedAt = null;
            if ($rawStatus === 'CANCEL') {
                $status = 'Cancelled';
                $cancelReason = 'ยกเลิกจากระบบเดิม';
            } elseif (in_array($rawStatus, ['SUCCESS', 'COMPLETE', 'COMPLETED'], true)) {
                $status = 'Completed';
                $completedAt = $createdTime;
            } elseif (in_array($rawStatus, ['WAIT', 'WAITING'], true)) {
                $status = 'Wait for Part';
            } elseif (in_array($rawStatus, ['PROGRESS', 'IN_PROGRESS', 'DOING'], true)) {
                $status = 'In Progress';
            }

            $imgPath = null;
            if (!empty($row['REPAIR_IMG']) && strlen((string)$row['REPAIR_IMG']) > 100) {
                $imgFilename = "repair_legacy_{$legacyId}.jpg";
                $targetPath = $uploadDir . $imgFilename;
                if (!file_exists($targetPath)) {
                    @file_put_contents($targetPath, $row['REPAIR_IMG']);
                }
                $imgPath = "backend/uploads/repairs/{$imgFilename}";
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
                ':priority_id' => $row['PRIORITY_ID'] ?: '01',
                ':priority_name' => $row['PRIORITY_NAME'] ?: 'ปกติ',
                ':requester_id' => !empty($row['USER_REQUEST_ID']) ? (int)$row['USER_REQUEST_ID'] : null,
                ':requester_name' => $row['USRE_REQUEST_NAME'] ?: 'ไม่ระบุผู้แจ้ง',
                ':requester_phone' => $row['USER_REQUEST_PHONE'] ?: null,
                ':department_id' => $row['DEPARTMENT_ID'] ?: null,
                ':department_name' => $row['HR_DEPARTMENT_SUB_NAME'] ?: ($row['HR_DEPARTMENT_NAME'] ?: 'กลุ่มงานบริหารทั่วไป'),
                ':asset_id' => $row['ARTICLE_ID'] ?: null,
                ':technician_id' => !empty($row['TECH_WANT_ID']) ? (int)$row['TECH_WANT_ID'] : null,
                ':technician_name' => $row['TECH_WANT_NAME'] ?: null,
                ':status' => $status,
                ':completed_at' => $completedAt,
                ':cancel_reason' => $cancelReason,
                ':image_before' => $imgPath,
                ':created_at' => $createdTime,
            ]);
            if ($insertRepair->rowCount() > 0) {
                $synced++;
            }
        }

        return $synced;
    } catch (Exception $e) {
        return 0;
    }
}
