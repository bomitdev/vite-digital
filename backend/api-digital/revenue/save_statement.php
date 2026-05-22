<?php
require_once '../../config.php';
require_once '../../auth_utils.php';

// Verify authentication and admin rights
$checkAuth = true;
$headers = apache_request_headers();
if (isset($headers['Authorization'])) {
    $matches = array();
    if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
        $token = $matches[1];
        if ($token) {
            $decoded = json_decode(base64_decode($token), true);
            if ($decoded && isset($decoded['department'])) {
                $dept = $decoded['department'];
                if (strpos($dept, 'สุขภาพดิจิทัล') === false && strpos($dept, 'บริหาร') === false && strpos($dept, 'ประกัน') === false) {
                    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Admin only.']);
                    exit;
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Invalid token scope.']);
                exit;
            }
        }
    }
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['target_id']) || !isset($data['statements']) || !is_array($data['statements'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data format.']);
    exit;
}

$target_id = $data['target_id'];

try {
    $pdo2->beginTransaction();

    foreach ($data['statements'] as $st) {
        $month = $st['month'];
        $amount = (isset($st['amount']) && $st['amount'] !== '') ? $st['amount'] : null;

        // Check if row already exists
        $check = $pdo2->prepare("SELECT id FROM revenue_results WHERE target_id = ? AND month = ?");
        $check->execute([$target_id, $month]);
        $existing = $check->fetch();

        if ($existing) {
            $stmt = $pdo2->prepare("UPDATE revenue_results SET statement_amount = ? WHERE id = ?");
            $stmt->execute([$amount, $existing['id']]);
        } else {
            // Only insert if the amount is actually being set
            if ($amount !== null && $amount !== '') {
                // report_date defaults to today because there wasn't one recorded by staff
                $stmt = $pdo2->prepare("INSERT INTO revenue_results 
                        (target_id, month, statement_amount, collected_amount, report_date) 
                    VALUES (?, ?, ?, 0.00, CURDATE())");
                $stmt->execute([$target_id, $month, $amount]);
            }
        }
    }

    $pdo2->commit();
    echo json_encode(['status' => 'success', 'message' => 'Statements saved successfully.']);
} catch (PDOException $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
