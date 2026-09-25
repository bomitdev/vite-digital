<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../../config.php';

try {
    $data = json_decode(file_get_contents("php://input"));
    
    if (empty($data->committee_id)) {
        throw new Exception("Committee ID is required");
    }

    if (isset($data->id) && $data->id > 0) {
        // UPDATE
        $sql = "UPDATE qi_improvement_plans SET 
            standard = :standard,
            recommendation = :recommendation,
            plan = :plan,
            period_2568 = :period_2568,
            period_2569 = :period_2569,
            period_2570 = :period_2570,
            period_2571 = :period_2571,
            indicator = :indicator,
            target = :target,
            responsible = :responsible,
            monitoring_period = :monitoring_period,
            result_2568 = :result_2568,
            result_2569 = :result_2569,
            result_2570 = :result_2570,
            result_2571 = :result_2571
            WHERE id = :id";
            
        $stmt = $pdo2->prepare($sql);
        $stmt->execute([
            ':standard' => $data->standard ?? null,
            ':recommendation' => $data->recommendation ?? null,
            ':plan' => $data->plan ?? null,
            ':period_2568' => $data->period_2568 ?? null,
            ':period_2569' => $data->period_2569 ?? null,
            ':period_2570' => $data->period_2570 ?? null,
            ':period_2571' => $data->period_2571 ?? null,
            ':indicator' => $data->indicator ?? null,
            ':target' => $data->target ?? null,
            ':responsible' => $data->responsible ?? null,
            ':monitoring_period' => $data->monitoring_period ?? null,
            ':result_2568' => $data->result_2568 ?? null,
            ':result_2569' => $data->result_2569 ?? null,
            ':result_2570' => $data->result_2570 ?? null,
            ':result_2571' => $data->result_2571 ?? null,
            ':id' => $data->id
        ]);
    } else {
        // INSERT
        $sql = "INSERT INTO qi_improvement_plans (
            committee_id, standard, recommendation, plan, 
            period_2568, period_2569, period_2570, period_2571, 
            indicator, target, responsible, monitoring_period, 
            result_2568, result_2569, result_2570, result_2571
        ) VALUES (
            :committee_id, :standard, :recommendation, :plan,
            :period_2568, :period_2569, :period_2570, :period_2571,
            :indicator, :target, :responsible, :monitoring_period,
            :result_2568, :result_2569, :result_2570, :result_2571
        )";
        
        $stmt = $pdo2->prepare($sql);
        $stmt->execute([
            ':committee_id' => $data->committee_id,
            ':standard' => $data->standard ?? null,
            ':recommendation' => $data->recommendation ?? null,
            ':plan' => $data->plan ?? null,
            ':period_2568' => $data->period_2568 ?? null,
            ':period_2569' => $data->period_2569 ?? null,
            ':period_2570' => $data->period_2570 ?? null,
            ':period_2571' => $data->period_2571 ?? null,
            ':indicator' => $data->indicator ?? null,
            ':target' => $data->target ?? null,
            ':responsible' => $data->responsible ?? null,
            ':monitoring_period' => $data->monitoring_period ?? null,
            ':result_2568' => $data->result_2568 ?? null,
            ':result_2569' => $data->result_2569 ?? null,
            ':result_2570' => $data->result_2570 ?? null,
            ':result_2571' => $data->result_2571 ?? null
        ]);
    }

    echo json_encode(["status" => "success", "message" => "Plan saved successfully"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
