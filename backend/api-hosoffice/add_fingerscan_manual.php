<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth_utils.php';

// Secure Auth
$userData = authGuard();
$user_id = $userData['uid'];


try {
    $data = json_decode(file_get_contents("php://input"), true);

    // 2. Prepare Entries
    $target_user_id = $data['target_user_id'] ?? 0;

    $entries = [];
    if (isset($data['entries']) && is_array($data['entries'])) {
        $entries = $data['entries'];
    } elseif (isset($data['datetime'])) {
        // Single mode compatibility
        $entries[] = [
            'datetime' => $data['datetime'],
            'type' => $data['type'] ?? 'C/In'
        ];
    }

    if (empty($entries)) {
        echo json_encode(["status" => "error", "message" => "No entries provided"]);
        exit;
    }

    // 3. User Lookup
    $stmtUser = $pdo3->prepare("SELECT FINGLE_ID, HR_FNAME, HR_LNAME FROM hr_person WHERE ID = :id");
    $stmtUser->execute([':id' => $target_user_id]);
    $target = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$target || empty($target['FINGLE_ID'])) {
        echo json_encode(["status" => "error", "message" => "User not found or no Finger ID linked"]);
        exit;
    }

    $fingle_id = $target['FINGLE_ID'];

    // 4. Insert Loop with Transaction
    $pdo3->beginTransaction();

    $sql = "INSERT INTO 10985_hos_fingerscan (fingerscan_user_id, fingerscan_datetime, fingerscan_inout) VALUES (:fid, :dt, :type)";
    $stmt = $pdo3->prepare($sql);

    $count = 0;
    foreach ($entries as $entry) {
        if (!empty($entry['datetime']) && !empty($entry['type'])) {
            $stmt->execute([
                ':fid' => $fingle_id,
                ':dt' => $entry['datetime'],
                ':type' => $entry['type']
            ]);
            $count++;
        }
    }

    $pdo3->commit();

    echo json_encode(["status" => "success", "message" => "Added $count records successfully"]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database Error: " . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Error: " . $e->getMessage()]);
}
