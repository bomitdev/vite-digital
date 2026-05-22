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
    $id = $data['id'] ?? null;

    if (!$id) {
        throw new Exception("ID is required.");
    }

    // Get software and asset info before deleting
    $stmtSwInfo = $pdo2->prepare("
        SELECT a.asset_code, a.name AS asset_name, asw.software_name 
        FROM asset_software asw 
        JOIN assets a ON asw.asset_id = a.id 
        WHERE asw.id = ?
    ");
    $stmtSwInfo->execute([$id]);
    $swInfo = $stmtSwInfo->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo2->prepare("DELETE FROM asset_software WHERE id = ?");
    $stmt->execute([$id]);

    // SYNCHRONIZE WITH CENTRAL SOFTWARE REGISTRY
    // Remove the installation record but keep the software defined in the registry
    if ($swInfo) {
        $machineName = $swInfo['asset_code'] . ' - ' . $swInfo['asset_name'];
        $softwareName = $swInfo['software_name'];

        // Get software id
        $stmtGetSwId = $pdo2->prepare("SELECT id FROM sw_software WHERE software_name = ?");
        $stmtGetSwId->execute([$softwareName]);
        $existingSw = $stmtGetSwId->fetch(PDO::FETCH_ASSOC);

        if ($existingSw) {
            $softwareId = $existingSw['id'];
            // Delete installation
            $stmtDelInst = $pdo2->prepare("DELETE FROM sw_installations WHERE software_id = ? AND machine_name = ?");
            $stmtDelInst->execute([$softwareId, $machineName]);
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Software deleted successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
