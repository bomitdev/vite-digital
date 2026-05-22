<?php
require '../config.php';

$user_id = 145; // From previous debug
$fingle_id = 252; // From previous debug
$start_date = '2025-12-01';
$end_date = '2025-12-31';

echo "Checking Data for User ID: $user_id (Fingle: $fingle_id) Dates: $start_date to $end_date\n";

// 1. Check Raw Data
$stmtRaw = $pdo3->prepare("SELECT COUNT(*) as cnt FROM 10985_hos_fingerscan WHERE fingerscan_user_id = :fid AND date(fingerscan_datetime) BETWEEN :start AND :end");
$stmtRaw->execute([':fid' => $fingle_id, ':start' => $start_date, ':end' => $end_date]);
$rawCount = $stmtRaw->fetch()['cnt'];
echo "Raw Data (10985_hos_fingerscan): $rawCount records\n";

// 2. Check Processed Data
$stmtCheck = $pdo3->prepare("SELECT COUNT(*) as cnt FROM 10985_hos_fingerscan_check WHERE fingerscan_user_id = :fid AND date(gdate) BETWEEN :start AND :end");
$stmtCheck->execute([':fid' => $fingle_id, ':start' => $start_date, ':end' => $end_date]);
$checkCount = $stmtCheck->fetch()['cnt'];
echo "Processed Data (10985_hos_fingerscan_check): $checkCount records\n";

// 3. Check if table has ANY data (maybe ID mismatch?)
$stmtAny = $pdo3->query("SELECT COUNT(*) as cnt FROM 10985_hos_fingerscan_check");
$totalCheck = $stmtAny->fetch()['cnt'];
echo "Total records in 10985_hos_fingerscan_check (System-wide): $totalCheck\n";
