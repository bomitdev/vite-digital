<?php
require_once '../../config.php';
$data = (object)[
    "asset_id" => 1,
    "borrower_name" => "test",
    "department" => "IT",
    "objective" => "test",
    "expected_return_date" => "2026-12-31"
];

try {
    $borrowDate = date('Y-m-d H:i:s');

    $stmt = $pdo2->prepare("INSERT INTO it_loans 
        (asset_id, borrower_name, department, objective, borrow_date, expected_return_date, status) 
        VALUES (:asset_id, :borrower_name, :department, :objective, :borrow_date, :expected_return_date, 'pending')");

    $stmt->execute([
        ':asset_id' => $data->asset_id,
        ':borrower_name' => trim($data->borrower_name),
        ':department' => trim($data->department),
        ':objective' => trim($data->objective),
        ':borrow_date' => $borrowDate,
        ':expected_return_date' => trim($data->expected_return_date)
    ]);

    // Fetch Asset Details for Line Notify
    $stmtAsset = $pdo2->prepare("SELECT asset_code, name FROM assets WHERE id = ?");
    $stmtAsset->execute([$data->asset_id]);
    $asset = $stmtAsset->fetch(PDO::FETCH_ASSOC);
    $assetText = $asset ? "[{$asset['asset_code']}] {$asset['name']}" : "Asset ID: " . $data->asset_id;

    echo "OK\n";
} catch (PDOException $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
