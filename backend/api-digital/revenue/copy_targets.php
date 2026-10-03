<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

require_once '../../config.php';
require_once '../../auth_utils.php';

$userData = authGuard();
if (!$userData) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

try {
    $data = json_decode(file_get_contents("php://input"), true);
    $fromYear = $data['from_year'] ?? null;
    $toYear = $data['to_year'] ?? null;

    if (!$fromYear || !$toYear) {
        throw new Exception("Missing from_year or to_year");
    }

    // $pdo2 is provided by config.php

    // Get all targets from from_year
    $stmt = $pdo2->prepare("SELECT * FROM revenue_targets WHERE fiscal_year = ?");
    $stmt->execute([$fromYear]);
    $targets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($targets) === 0) {
        echo json_encode(["status" => "error", "message" => "ไม่พบข้อมูลเป้าหมายในปี $fromYear"]);
        exit;
    }

    $inserted = 0;
    foreach ($targets as $target) {
        // Check if a target with same name and toYear already exists to prevent duplicates
        $check = $pdo2->prepare("SELECT id FROM revenue_targets WHERE fiscal_year = ? AND revenue_name = ?");
        $check->execute([$toYear, $target['revenue_name']]);
        if ($check->fetch()) {
            continue; // Skip if already exists
        }

        $ins = $pdo2->prepare("INSERT INTO revenue_targets 
            (fiscal_year, revenue_name, unit_price, target_amount, target_per_month, claim_program, responsible_person) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $ins->execute([
            $toYear,
            $target['revenue_name'],
            $target['unit_price'],
            $target['target_amount'],
            $target['target_per_month'],
            $target['claim_program'] ?? null,
            $target['responsible_person']
        ]);
        $inserted++;
    }

    echo json_encode([
        "status" => "success", 
        "message" => "คัดลอกรายการสำเร็จจำนวน $inserted รายการ",
        "inserted" => $inserted
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
