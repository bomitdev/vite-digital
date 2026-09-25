<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config.php';

try {
    // Get all committees
    $sqlTeams = "SELECT id, name FROM qi_committees ORDER BY name ASC";
    $stmtTeams = $pdo2->query($sqlTeams);
    $teams = $stmtTeams->fetchAll(PDO::FETCH_ASSOC);

    // Get all plans to aggregate
    $sqlPlans = "SELECT committee_id, 
                        result_2568, result_2569, result_2570, result_2571 
                 FROM qi_improvement_plans";
    $stmtPlans = $pdo2->query($sqlPlans);
    $plans = $stmtPlans->fetchAll(PDO::FETCH_ASSOC);

    $dashboardData = [];

    foreach ($teams as $team) {
        $teamPlans = array_filter($plans, function($p) use ($team) {
            return $p['committee_id'] == $team['id'];
        });

        $totalPlans = count($teamPlans);
        
        $reported2568 = 0;
        $reported2569 = 0;
        $reported2570 = 0;
        $reported2571 = 0;

        foreach ($teamPlans as $plan) {
            if (!empty(trim($plan['result_2568'] ?? ''))) $reported2568++;
            if (!empty(trim($plan['result_2569'] ?? ''))) $reported2569++;
            if (!empty(trim($plan['result_2570'] ?? ''))) $reported2570++;
            if (!empty(trim($plan['result_2571'] ?? ''))) $reported2571++;
        }

        $dashboardData[] = [
            'id' => $team['id'],
            'name' => $team['name'],
            'total_plans' => $totalPlans,
            'reported_2568' => $reported2568,
            'progress_2568' => $totalPlans > 0 ? round(($reported2568 / $totalPlans) * 100) : 0,
            'reported_2569' => $reported2569,
            'progress_2569' => $totalPlans > 0 ? round(($reported2569 / $totalPlans) * 100) : 0,
            'reported_2570' => $reported2570,
            'progress_2570' => $totalPlans > 0 ? round(($reported2570 / $totalPlans) * 100) : 0,
            'reported_2571' => $reported2571,
            'progress_2571' => $totalPlans > 0 ? round(($reported2571 / $totalPlans) * 100) : 0,
        ];
    }

    echo json_encode(["status" => "success", "data" => array_values($dashboardData)]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
