<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';

$userData = authGuard();
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->project_name)) {
    try {
        $id = isset($data->id) ? $data->id : null;
        $project_name = $data->project_name;
        $description = isset($data->description) ? $data->description : null;
        $status = isset($data->status) ? $data->status : 'pending';
        $progress = isset($data->progress) ? (int)$data->progress : 0;
        $start_date = !empty($data->start_date) ? $data->start_date : null;
        $target_date = !empty($data->target_date) ? $data->target_date : null;
        $manager_name = isset($data->manager_name) ? $data->manager_name : null;
        
        $quantity = isset($data->quantity) ? (int)$data->quantity : 0;
        $completed_quantity = isset($data->completed_quantity) ? (int)$data->completed_quantity : 0;
        $unit_price = isset($data->unit_price) ? (float)$data->unit_price : 0.00;
        $fiscal_year = isset($data->fiscal_year) ? $data->fiscal_year : null;
        $category_id = isset($data->category_id) ? (int)$data->category_id : null;
        
        $quarters = null;
        if (isset($data->quarters) && is_array($data->quarters)) {
            $quarters = implode(',', $data->quarters);
        }
        
        // Auto calculate progress based on quantity if not provided explicitly, or if it's completed
        if ($quantity > 0) {
            $progress = round(($completed_quantity / $quantity) * 100);
            if ($progress >= 100) {
                $progress = 100;
                $status = 'completed';
            }
        }

        $completed_date = null;
        if ($status === 'completed') {
            $completed_date = !empty($data->completed_date) ? $data->completed_date : date('Y-m-d');
            $progress = 100;
        }

        if ($id) {
            // Update
            $sql = "UPDATE it_projects SET 
                    project_name = :project_name, 
                    description = :description, 
                    status = :status, 
                    progress = :progress, 
                    start_date = :start_date, 
                    target_date = :target_date, 
                    completed_date = :completed_date, 
                    manager_name = :manager_name,
                    quantity = :quantity,
                    completed_quantity = :completed_quantity,
                    unit_price = :unit_price,
                    fiscal_year = :fiscal_year,
                    category_id = :category_id,
                    quarters = :quarters
                    WHERE id = :id";
            $stmt = $pdo2->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        } else {
            // Insert
            $sql = "INSERT INTO it_projects 
                    (project_name, description, status, progress, start_date, target_date, completed_date, manager_name, quantity, completed_quantity, unit_price, fiscal_year, category_id, quarters) 
                    VALUES 
                    (:project_name, :description, :status, :progress, :start_date, :target_date, :completed_date, :manager_name, :quantity, :completed_quantity, :unit_price, :fiscal_year, :category_id, :quarters)";
            $stmt = $pdo2->prepare($sql);
        }

        $stmt->bindParam(':project_name', $project_name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':progress', $progress, PDO::PARAM_INT);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':target_date', $target_date);
        $stmt->bindParam(':completed_date', $completed_date);
        $stmt->bindParam(':manager_name', $manager_name);
        $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $stmt->bindParam(':completed_quantity', $completed_quantity, PDO::PARAM_INT);
        $stmt->bindParam(':unit_price', $unit_price);
        $stmt->bindParam(':fiscal_year', $fiscal_year);
        $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
        $stmt->bindParam(':quarters', $quarters);

        if ($stmt->execute()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'บันทึกข้อมูลโครงการสำเร็จ'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'ไม่สามารถบันทึกข้อมูลได้'
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Database Error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'กรุณากรอกข้อมูลที่จำเป็น (ชื่อโครงการ)'
    ]);
}
