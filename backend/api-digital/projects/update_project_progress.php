<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';

$userData = authGuard();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id) && isset($data->completed_quarters)) {
    try {
        $id = (int)$data->id;
        $completed_quarters_arr = is_array($data->completed_quarters) ? $data->completed_quarters : [];
        $completed_quarters = implode(',', $completed_quarters_arr);
        $completed_quantity = count($completed_quarters_arr);
        
        // Fetch current quantity and quarters to calculate progress
        $stmt = $pdo2->prepare("SELECT quantity, quarters FROM it_projects WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($project) {
            $quantity = (int)$project['quantity'];
            $quarters_str = $project['quarters'];
            $total_quarters_arr = $quarters_str ? explode(',', $quarters_str) : [];
            $total_quarters_count = count($total_quarters_arr);
            $checked_quarters_count = count($completed_quarters_arr);
            
            $progress = 0;
            $status = 'in_progress';
            $completed_date = null;
            
            if ($total_quarters_count > 0) {
                $progress = round(($checked_quarters_count / $total_quarters_count) * 100);
            } else {
                $progress = $checked_quarters_count > 0 ? 100 : 0;
            }
            
            // Calculate completed_quantity proportionally
            $completed_quantity = round(($progress / 100) * $quantity);
            
            if ($progress >= 100) {
                $progress = 100;
                $status = 'completed';
                $completed_date = date('Y-m-d');
            } else if ($progress === 0 && $completed_quantity === 0) {
                $status = 'pending';
            }
            
            $sql = "UPDATE it_projects SET 
                    completed_quantity = :completed_quantity,
                    completed_quarters = :completed_quarters,
                    progress = :progress,
                    status = :status,
                    completed_date = :completed_date
                    WHERE id = :id";
                    
            $updateStmt = $pdo2->prepare($sql);
            $updateStmt->bindParam(':completed_quantity', $completed_quantity, PDO::PARAM_INT);
            $updateStmt->bindParam(':completed_quarters', $completed_quarters);
            $updateStmt->bindParam(':progress', $progress, PDO::PARAM_INT);
            $updateStmt->bindParam(':status', $status);
            $updateStmt->bindParam(':completed_date', $completed_date);
            $updateStmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($updateStmt->execute()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'อัปเดตความคืบหน้าสำเร็จ',
                    'data' => [
                        'progress' => $progress,
                        'status' => $status,
                        'completed_quantity' => $completed_quantity,
                        'completed_quarters' => $completed_quarters
                    ]
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถบันทึกข้อมูลได้']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลโครงการ']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
}
