<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';

session_start();

$user_id = $_SESSION['user_id'] ?? 0;

if (!$user_id) {
    // Fallback to Bearer Token parsing
    $userData = authOptional();
    if ($userData) {
        if (isset($userData['uid'])) {
            $user_id = $userData['uid'];
        } elseif (isset($userData['data']['id'])) {
            $user_id = $userData['data']['id'];
        }
    }
}

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

// Allowed enum values mapping
$allowed_calc_types = ['percentage', 'ratio', 'rate', 'boolean', 'count', 'amount'];
$allowed_kpi_levels = ['กระทรวง', 'เขต', 'จังหวัด', 'โรงพยาบาล'];
$allowed_periodicities = ['month', 'quarter', 'Semiannual report', 'year'];

try {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!is_array($data) || empty($data)) {
        throw new Exception("No data to import from JSON payload");
    }

    $pdo2->beginTransaction();

    $importedCount = 0;
    $updatedCount = 0;
    $errors = [];

    // Pre-fetch valid categories for validation
    $stmtCats = $pdo2->query("SELECT id FROM kpi_categories");
    $validCategories = $stmtCats->fetchAll(PDO::FETCH_COLUMN);

    foreach ($data as $index => $item) {
        $rowNum = $index + 2; // Assume Excel row 1 is header
        $name = trim($item['kpi_name'] ?? '');
        $category_id = intval($item['category_id'] ?? 0);
        
        if (empty($name)) {
            $errors[] = "แถว $rowNum: ชื่อตัวชี้วัดว่างเปล่า";
            continue; 
        }

        if (empty($category_id) || !in_array($category_id, $validCategories)) {
            $errors[] = "แถว $rowNum: หมวดหมู่ไม่ถูกต้อง หรือไม่มีในระบบ (category_id: $category_id)";
            continue;
        }

        $code = trim($item['kpi_code'] ?? '');
        $fiscal_year = intval($item['fiscal_year'] ?? 0);
        $desc = $item['description'] ?? '';
        
        $calc_type = $item['calculation_type'] ?? 'percentage';
        if (!in_array($calc_type, $allowed_calc_types)) {
            $errors[] = "แถว $rowNum: ประเภทการคำนวณไม่ถูกต้อง ('$calc_type')";
            continue;
        }

        $kpi_level = $item['kpi_level'] ?? 'โรงพยาบาล';
        if (!in_array($kpi_level, $allowed_kpi_levels)) {
            $errors[] = "แถว $rowNum: ระดับตัวชี้วัดไม่ถูกต้อง ('$kpi_level')";
            continue;
        }

        $periodicity = $item['kpi_periodicity'] ?? 'month';
        if (!in_array($periodicity, $allowed_periodicities)) {
            $errors[] = "แถว $rowNum: รอบการประเมินไม่ถูกต้อง ('$periodicity')";
            continue;
        }

        $target = $item['target_value'] ?? 0;
        if (!is_numeric($target)) {
            $errors[] = "แถว $rowNum: ค่าเป้าหมายต้องเป็นตัวเลข";
            continue;
        }

        $op = $item['target_operator'] ?? '>=';
        $unit = $item['unit'] ?? '%';
        $resp_person = $item['responsible_person'] ?? '';
        $resp_unit = $item['responsible_unit'] ?? '';

        try {
            // Check for existing KPI
            $existingId = null;
            if (!empty($code)) {
                $checkStmt = $pdo2->prepare("SELECT id FROM kpi_definitions WHERE code = ? AND fiscal_year = ?");
                $checkStmt->execute([$code, $fiscal_year]);
                $existingId = $checkStmt->fetchColumn();
            }
            
            if (!$existingId) {
                $checkStmt = $pdo2->prepare("SELECT id FROM kpi_definitions WHERE name = ? AND fiscal_year = ?");
                $checkStmt->execute([$name, $fiscal_year]);
                $existingId = $checkStmt->fetchColumn();
            }

            if ($existingId) {
                // Update
                $sql = "UPDATE kpi_definitions SET 
                        code = :code, category_id = :cat_id, description = :desc, 
                        calculation_type = :calc_type, kpi_level = :kpi_level, 
                        kpi_periodicity = :periodicity, target_value = :target, 
                        target_operator = :op, unit = :unit, responsible_person = :resp_person, 
                        responsible_unit = :resp_unit
                    WHERE id = :id";
                $stmt = $pdo2->prepare($sql);
                $stmt->execute([
                    ':code' => $code,
                    ':cat_id' => $category_id,
                    ':desc' => $desc,
                    ':calc_type' => $calc_type,
                    ':kpi_level' => $kpi_level,
                    ':periodicity' => $periodicity,
                    ':target' => $target,
                    ':op' => $op,
                    ':unit' => $unit,
                    ':resp_person' => $resp_person,
                    ':resp_unit' => $resp_unit,
                    ':id' => $existingId
                ]);
                $updatedCount++;
            } else {
                // Insert
                $sql = "INSERT INTO kpi_definitions 
                            (code, category_id, name, description, calculation_type, kpi_level, kpi_periodicity, target_value, target_operator, unit, responsible_person, responsible_unit, fiscal_year) 
                        VALUES 
                            (:code, :cat_id, :name, :desc, :calc_type, :kpi_level, :periodicity, :target, :op, :unit, :resp_person, :resp_unit, :fiscal_year)";
                $stmt = $pdo2->prepare($sql);
                $stmt->execute([
                    ':code' => $code,
                    ':cat_id' => $category_id,
                    ':name' => $name,
                    ':desc' => $desc,
                    ':calc_type' => $calc_type,
                    ':kpi_level' => $kpi_level,
                    ':periodicity' => $periodicity,
                    ':target' => $target,
                    ':op' => $op,
                    ':unit' => $unit,
                    ':resp_person' => $resp_person,
                    ':resp_unit' => $resp_unit,
                    ':fiscal_year' => $fiscal_year
                ]);
                $importedCount++;
            }
        } catch (PDOException $ex) {
            $errors[] = "แถว $rowNum: เกิดข้อผิดพลาดในการบันทึกข้อมูล (" . $ex->getMessage() . ")";
        }
    }

    $pdo2->commit();
    
    $message = "เพิ่มใหม่ $importedCount รายการ, อัปเดต $updatedCount รายการ";
    if (count($errors) > 0) {
        $message .= " (พบข้อผิดพลาด " . count($errors) . " รายการ)";
    }
    
    echo json_encode([
        'status' => 'success', 
        'message' => $message,
        'errors' => $errors
    ]);
} catch (Exception $e) {
    if (isset($pdo2) && $pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
