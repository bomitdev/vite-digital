<?php
require_once '../../config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));

if (!$data || !isset($data->id)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameter: id']);
    exit;
}

try {
    $sql = "UPDATE sw_communication_channels SET 
            channel_name = :channel_name,
            category = :category,
            channel_type = :channel_type,
            objective = :objective,
            target_audience = :target_audience,
            contact_detail = :contact_detail,
            responsible_person = :responsible_person,
            department = :department,
            sla_response_time = :sla_response_time,
            status = :status,
            usage_frequency = :usage_frequency,
            platform_tool = :platform_tool,
            formality_level = :formality_level,
            strengths = :strengths,
            limitations = :limitations,
            risks = :risks,
            improvement_plan = :improvement_plan
            WHERE id = :id";

    $stmt = $pdo2->prepare($sql);

    $stmt->execute([
        ':id' => $data->id,
        ':channel_name' => $data->channel_name,
        ':category' => $data->category,
        ':channel_type' => $data->channel_type,
        ':objective' => $data->objective ?? null,
        ':target_audience' => $data->target_audience ?? null,
        ':contact_detail' => $data->contact_detail ?? null,
        ':responsible_person' => $data->responsible_person ?? null,
        ':department' => $data->department ?? null,
        ':sla_response_time' => $data->sla_response_time ?? null,
        ':status' => $data->status ?? 'Active',
        ':usage_frequency' => $data->usage_frequency ?? null,
        ':platform_tool' => $data->platform_tool ?? null,
        ':formality_level' => $data->formality_level ?? null,
        ':strengths' => $data->strengths ?? null,
        ':limitations' => $data->limitations ?? null,
        ':risks' => $data->risks ?? null,
        ':improvement_plan' => $data->improvement_plan ?? null
    ]);

    echo json_encode(['success' => true, 'message' => 'Communication channel updated successfully.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
