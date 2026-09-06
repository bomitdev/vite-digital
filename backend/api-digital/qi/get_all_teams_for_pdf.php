<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config.php';

try {
    // Get all committees ordered by sort_order
    $sqlTeams = "SELECT id, name, description FROM qi_committees ORDER BY sort_order ASC, id ASC";
    $stmtTeams = $pdo2->query($sqlTeams);
    $teams = $stmtTeams->fetchAll(PDO::FETCH_ASSOC);

    // Array to hold the full hierarchy
    $result = [];

    // Prepared statement for members to reuse
    $sqlMembers = "SELECT id, officer_name, role 
                   FROM qi_committee_members 
                   WHERE committee_id = :committee_id 
                   ORDER BY 
                       CASE role 
                           WHEN 'ประธาน' THEN 1 
                           WHEN 'ประธานกรรมการ' THEN 1
                           WHEN 'รองประธาน' THEN 2 
                           WHEN 'รองประธานกรรมการ' THEN 2
                           WHEN 'กรรมการ' THEN 3 
                           WHEN 'กรรมการและเลขานุการ' THEN 4
                           WHEN 'กรรมการและผู้ช่วยเลขา' THEN 5
                           WHEN 'กรรมการและผู้ช่วยเลขาฯ' THEN 5
                           WHEN 'เลขานุการ' THEN 6
                           WHEN 'ผู้ช่วยเลขานุการ' THEN 7
                           ELSE 8 
                       END, 
                       officer_name ASC";
    $stmtMembers = $pdo2->prepare($sqlMembers);

    foreach ($teams as $team) {
        $stmtMembers->execute([':committee_id' => $team['id']]);
        $members = $stmtMembers->fetchAll(PDO::FETCH_ASSOC);
        
        // Add members to team object
        $team['members'] = $members;
        
        $result[] = $team;
    }

    echo json_encode(["status" => "success", "data" => $result]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
