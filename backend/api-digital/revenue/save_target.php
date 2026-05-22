<?php
require_once '../../config.php';
require_once '../../auth_utils.php';

// Verify authentication
$user = authGuard();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['revenue_name']) || !isset($data['fiscal_year']) || !isset($data['target_amount'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    exit;
}

$id = $data['id'] ?? null;
$revenue_name = $data['revenue_name'];
$fiscal_year = $data['fiscal_year'];
$target_amount = $data['target_amount'];
$responsible_person = $data['responsible_person'] ?? '';
$target_per_month = $data['target_per_month'] ?? null;
$unit_price = $data['unit_price'] ?? null;
$claim_program = $data['claim_program'] ?? null;

try {
    if ($id) {
        $stmt = $pdo2->prepare("UPDATE revenue_targets SET 
                revenue_name = ?, fiscal_year = ?, target_amount = ?, target_per_month = ?,
                unit_price = ?, claim_program = ?,
                responsible_person = ?
            WHERE id = ?");
        $stmt->execute([$revenue_name, $fiscal_year, $target_amount, $target_per_month, $unit_price, $claim_program, $responsible_person, $id]);
    } else {
        $stmt = $pdo2->prepare("INSERT INTO revenue_targets 
                (revenue_name, fiscal_year, target_amount, target_per_month, unit_price, claim_program, responsible_person) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$revenue_name, $fiscal_year, $target_amount, $target_per_month, $unit_price, $claim_program, $responsible_person]);
    }

    echo json_encode(['status' => 'success', 'message' => 'Target saved successfully.']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
