<?php
require_once '../../config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));

if (!$data || !isset($data->channel_name) || !isset($data->category) || !isset($data->channel_type)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters: channel_name, category, or channel_type']);
    exit;
}

try {
    $sql = "INSERT INTO sw_communication_channels 
            (channel_name, category, channel_type, objective, target_audience, contact_detail, responsible_person, department, sla_response_time, status, usage_frequency, platform_tool, formality_level, strengths, limitations, risks, improvement_plan) 
            VALUES (:channel_name, :category, :channel_type, :objective, :target_audience, :contact_detail, :responsible_person, :department, :sla_response_time, :status, :usage_frequency, :platform_tool, :formality_level, :strengths, :limitations, :risks, :improvement_plan)";

    $stmt = $pdo2->prepare($sql);

    $stmt->execute([
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

    echo json_encode(['success' => true, 'message' => 'Communication channel added successfully.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
