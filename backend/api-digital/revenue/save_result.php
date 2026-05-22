<?php
require_once '../../config.php';
require_once '../../auth_utils.php';

// Verify authentication
$user = authGuard();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['target_id']) || !isset($data['month']) || !isset($data['collected_amount'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    exit;
}

$id = $data['id'] ?? null;
$target_id = $data['target_id'];
$month = $data['month'];
$achieved_items = isset($data['achieved_items']) && $data['achieved_items'] !== '' ? $data['achieved_items'] : null;
$collected_amount = isset($data['collected_amount']) && $data['collected_amount'] !== '' ? $data['collected_amount'] : 0.00;
$report_date = $data['report_date'] ?? date('Y-m-d');
$remark = $data['remark'] ?? '';

try {
    if ($id) {
        $stmt = $pdo2->prepare("UPDATE revenue_results SET 
                month = ?, achieved_items = ?, collected_amount = ?, report_date = ?, remark = ?
            WHERE id = ?");
        $stmt->execute([$month, $achieved_items, $collected_amount, $report_date, $remark, $id]);
    } else {
        // Option check if result for that month exists?
        $check = $pdo2->prepare("SELECT id FROM revenue_results WHERE target_id = ? AND month = ?");
        $check->execute([$target_id, $month]);
        $existing = $check->fetch();

        if ($existing) {
            $stmt = $pdo2->prepare("UPDATE revenue_results SET 
                    achieved_items = ?, collected_amount = ?, report_date = ?, remark = ?
                WHERE id = ?");
            $stmt->execute([$achieved_items, $collected_amount, $report_date, $remark, $existing['id']]);
        } else {
            $stmt = $pdo2->prepare("INSERT INTO revenue_results 
                    (target_id, month, achieved_items, collected_amount, report_date, remark) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$target_id, $month, $achieved_items, $collected_amount, $report_date, $remark]);
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'Result saved successfully.']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
