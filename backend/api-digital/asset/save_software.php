<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . '/../../config.php';

try {
    // Check if the pdo2 connection is available (from config.php)
    if (!isset($pdo2)) {
        throw new Exception("Database connection error: \$pdo2 is not set.");
    }
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data['asset_id']) || empty($data['software_name'])) {
        throw new Exception("Asset ID and Software Name are required.");
    }

    if (empty($data['id'])) {
        // Insert
        $sql = "INSERT INTO asset_software (asset_id, software_name, version, license_key, license_type, install_date, expiry_date, notes) 
                VALUES (:aid, :name, :ver, :key, :type, :install, :expiry, :notes)";
    } else {
        // Update
        $sql = "UPDATE asset_software SET 
                software_name = :name, version = :ver, license_key = :key, license_type = :type, 
                install_date = :install, expiry_date = :expiry, notes = :notes
                WHERE id = :id";
    }

    $stmt = $pdo2->prepare($sql);

    $params = [
        ':name' => $data['software_name'],
        ':ver' => $data['version'] ?? '',
        ':key' => $data['license_key'] ?? '',
        ':type' => $data['license_type'] ?? '',
        ':install' => !empty($data['install_date']) ? $data['install_date'] : null,
        ':expiry' => !empty($data['expiry_date']) ? $data['expiry_date'] : null,
        ':notes' => $data['notes'] ?? ''
    ];

    if (empty($data['id'])) {
        $params[':aid'] = $data['asset_id'];
    } else {
        $params[':id'] = $data['id'];
    }

    $stmt->execute($params);
    $assetSoftwareId = empty($data['id']) ? $pdo2->lastInsertId() : $data['id'];

    // --- SYNCHRONIZE WITH CENTRAL SOFTWARE REGISTRY ---
    // 1. Get the Asset Code and Name to form machine_name, and responsible person as user_name
    $stmtAsset = $pdo2->prepare("SELECT asset_code, name, responsible_person FROM assets WHERE id = ?");
    $stmtAsset->execute([$data['asset_id']]);
    $asset = $stmtAsset->fetch(PDO::FETCH_ASSOC);

    if ($asset) {
        $machineName = $asset['asset_code'] . ' - ' . $asset['name'];
        $userName = $asset['responsible_person'] ?? null;

        // 2. Check if the software exists in sw_software by name
        $stmtSw = $pdo2->prepare("SELECT id FROM sw_software WHERE software_name = ?");
        $stmtSw->execute([$data['software_name']]);
        $existingSw = $stmtSw->fetch(PDO::FETCH_ASSOC);

        $softwareId = null;
        if ($existingSw) {
            $softwareId = $existingSw['id'];
        } else {
            // Insert into sw_software
            $sqlInsSw = "INSERT INTO sw_software (software_name, version, license_key, license_type) VALUES (?, ?, ?, ?)";
            $stmtInsSw = $pdo2->prepare($sqlInsSw);
            $stmtInsSw->execute([
                $data['software_name'],
                $data['version'] ?? null,
                $data['license_key'] ?? null,
                $data['license_type'] ?? null
            ]);
            $softwareId = $pdo2->lastInsertId();
        }

        // 3. Check if an installation already exists for this machine_name + software_id
        if ($softwareId && $machineName) {
            $stmtInstExist = $pdo2->prepare("SELECT id FROM sw_installations WHERE software_id = ? AND machine_name = ?");
            $stmtInstExist->execute([$softwareId, $machineName]);
            $existingInst = $stmtInstExist->fetch(PDO::FETCH_ASSOC);

            if (!$existingInst) {
                // Insert into sw_installations
                $sqlInsInst = "INSERT INTO sw_installations (software_id, machine_name, user_name, install_date) VALUES (?, ?, ?, ?)";
                $stmtInsInst = $pdo2->prepare($sqlInsInst);
                $stmtInsInst->execute([
                    $softwareId,
                    $machineName,
                    $userName,
                    !empty($data['install_date']) ? $data['install_date'] : date('Y-m-d')
                ]);
            }
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Software saved successfully', 'id' => $assetSoftwareId]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
