<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';

$userData = authGuard();
$data = json_decode(file_get_contents("php://input"));

$from_year = !empty($data->from_year) ? trim($data->from_year) : '2569';
$to_year = !empty($data->to_year) ? trim($data->to_year) : '2570';
$action = !empty($data->action) ? trim($data->action) : 'copy'; // 'copy' or 'update'

try {
    if ($action === 'copy') {
        // Query projects from from_year
        $sql = "SELECT project_name, description, quantity, unit_price, category_id, quarters, manager_name 
                FROM it_projects 
                WHERE fiscal_year = :from_year OR (:from_year = '2569' AND (fiscal_year IS NULL OR fiscal_year = ''))";
        $stmt = $pdo2->prepare($sql);
        $stmt->bindParam(':from_year', $from_year);
        $stmt->execute();
        $sourceProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($sourceProjects)) {
            echo json_encode([
                'status' => 'error',
                'message' => "ไม่พบข้อมูลโครงการของปีงบประมาณ {$from_year}"
            ]);
            exit;
        }

        $insertSql = "INSERT INTO it_projects 
                      (project_name, description, quantity, unit_price, fiscal_year, category_id, quarters, manager_name, status, progress, completed_quantity, completed_quarters, start_date, target_date) 
                      VALUES 
                      (:project_name, :description, :quantity, :unit_price, :to_year, :category_id, :quarters, :manager_name, 'pending', 0, 0, NULL, :start_date, :target_date)";
        $insertStmt = $pdo2->prepare($insertSql);

        $copiedCount = 0;
        $startDate = (intval($to_year) - 544) . "-10-01"; // e.g. 2026-10-01 for 2570
        $targetDate = (intval($to_year) - 543) . "-09-30"; // e.g. 2027-09-30 for 2570

        foreach ($sourceProjects as $p) {
            $insertStmt->execute([
                ':project_name' => $p['project_name'],
                ':description' => $p['description'],
                ':quantity' => $p['quantity'] ?? 0,
                ':unit_price' => $p['unit_price'] ?? 0,
                ':to_year' => $to_year,
                ':category_id' => $p['category_id'],
                ':quarters' => $p['quarters'],
                ':manager_name' => $p['manager_name'],
                ':start_date' => $startDate,
                ':target_date' => $targetDate
            ]);
            $copiedCount++;
        }

        echo json_encode([
            'status' => 'success',
            'message' => "คัดลอกโครงการจากปี {$from_year} ไปยังปี {$to_year} เรียบร้อยแล้วจำนวน {$copiedCount} โครงการ",
            'count' => $copiedCount
        ]);
    } elseif ($action === 'update') {
        // Update fiscal_year of existing projects from from_year to to_year
        $sql = "UPDATE it_projects 
                SET fiscal_year = :to_year 
                WHERE fiscal_year = :from_year OR (:from_year = '2569' AND (fiscal_year IS NULL OR fiscal_year = ''))";
        $stmt = $pdo2->prepare($sql);
        $stmt->bindParam(':to_year', $to_year);
        $stmt->bindParam(':from_year', $from_year);
        $stmt->execute();
        $updatedCount = $stmt->rowCount();

        echo json_encode([
            'status' => 'success',
            'message' => "ปรับเปลี่ยนปีงบประมาณเป็นปี {$to_year} เรียบร้อยแล้วจำนวน {$updatedCount} โครงการ",
            'count' => $updatedCount
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action'
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database Error: ' . $e->getMessage()
    ]);
}
