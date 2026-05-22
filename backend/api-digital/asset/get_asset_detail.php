<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

try {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    if (!$id) {
        throw new Exception("Asset ID is required.");
    }

    // 1. Get Asset Info
    $stmt = $pdo2->prepare("SELECT * FROM assets WHERE id = ?");
    $stmt->execute([$id]);
    $asset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$asset) {
        throw new Exception("Asset not found.");
    }

    // 2. Get Installed Software
    $stmt = $pdo2->prepare("SELECT id, software_name, version, license_key, license_type FROM asset_software WHERE asset_id = ? ORDER BY created_at DESC");
    $stmt->execute([$id]);
    $software = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2.B Get Installed Software from New Registration System
    if (!empty($asset['asset_code']) && !empty($asset['name'])) {
        $machineNameMatch = $asset['asset_code'] . ' - ' . $asset['name'];
        $stmtNew = $pdo2->prepare("
            SELECT s.software_name, s.version, s.license_key, s.license_type 
            FROM sw_installations i
            JOIN sw_software s ON i.software_id = s.id
            WHERE i.machine_name = ?
            ORDER BY i.install_date DESC
        ");
        $stmtNew->execute([$machineNameMatch]);
        $newSoftware = $stmtNew->fetchAll(PDO::FETCH_ASSOC);

        // Merge existing asset_software with newSoftware, deduplicating by software name
        foreach ($newSoftware as $nsw) {
            $exists = false;
            foreach ($software as &$localSw) {
                if (strtolower($localSw['software_name']) === strtolower($nsw['software_name'])) {
                    $exists = true;
                    $localSw['is_synced'] = true;
                    break;
                }
            }
            unset($localSw);

            if (!$exists) {
                $software[] = [
                    'id' => 'sw_reg_' . rand(1000, 99999),
                    'software_name' => $nsw['software_name'] . ' (Registered)',
                    'version' => $nsw['version'],
                    'license_key' => $nsw['license_key'],
                    'license_type' => $nsw['license_type']
                ];
            }
        }
    }

    // 3. Get Maintenance History
    // A. Manual Maintenance Logs
    $stmt = $pdo2->prepare("SELECT * FROM asset_maintenance WHERE asset_id = ? ORDER BY repair_date DESC");
    $stmt->execute([$id]);
    $maintenanceLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // B. Computer Repair Requests (linked via asset_code)
    $repairRequests = [];
    if (!empty($asset['asset_code'])) {
        $stmt = $pdo2->prepare("SELECT * FROM computer_repair_requests WHERE asset_code = ? ORDER BY created_at DESC");
        $stmt->execute([$asset['asset_code']]);
        $repairRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Merge and Normalize
    $maintenance = [];
    foreach ($maintenanceLogs as $log) {
        $maintenance[] = [
            'id' => 'm_' . $log['id'],
            'repair_date' => $log['repair_date'],
            'issue' => $log['issue'],
            'solution' => $log['solution'],
            'cost' => $log['cost'],
            'technician' => $log['technician'],
            'status' => $log['status'],
            'source' => 'Manual'
        ];
    }
    foreach ($repairRequests as $req) {
        $maintenance[] = [
            'id' => 'r_' . $req['id'],
            'repair_date' => $req['created_at'],
            'issue' => $req['issue_title'] . ' (' . $req['issue_description'] . ')',
            'solution' => $req['technician_comment'],
            'cost' => 0,
            'technician' => $req['technician_name'],
            'status' => $req['status'],
            'source' => 'Request'
        ];
    }

    // Sort by Date DESC
    usort($maintenance, function ($a, $b) {
        return strtotime($b['repair_date']) - strtotime($a['repair_date']);
    });

    echo json_encode([
        'status' => 'success',
        'data' => [
            'asset' => $asset,
            'software' => $software,
            'maintenance' => $maintenance
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
