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

try {
    $data = json_decode(file_get_contents("php://input"), true);

    // Validate required fields
    if (empty($data['kpi_name'])) {
        throw new Exception("Missing required field (kpi_name)");
    }

    $cat_id = $data['category_id'] ?? null;
    
    // Handle new category creation
    if ($cat_id === 'new' && !empty($data['new_category_name'])) {
        $stmtCat = $pdo2->prepare("INSERT INTO kpi_categories (name, description) VALUES (?, ?)");
        $stmtCat->execute([$data['new_category_name'], $data['new_category_name']]);
        $cat_id = $pdo2->lastInsertId();
    } elseif (empty($cat_id)) {
        throw new Exception("Missing required field (category_id)");
    }

    // Check if ID exists (Update) or not (Insert)
    if (!empty($data['id'])) {
        $sql = "UPDATE kpi_definitions SET 
                    code = :code,
                    category_id = :cat_id,
                    name = :name,
                    description = :desc,
                    calculation_type = :calc_type,
                    kpi_level = :kpi_level,
                    kpi_periodicity = :periodicity,
                    target_value = :target,
                    target_operator = :op,
                    unit = :unit,
                    responsible_person = :resp_person,
                    responsible_unit = :resp_unit,
                    numerator_label = :num_label,
                    denominator_label = :den_label,
                    multiplier = :multiplier,
                    fiscal_year = :fiscal_year
                WHERE id = :id";
        $stmt = $pdo2->prepare($sql);
        $stmt->execute([
            ':code' => $data['kpi_code'] ?? null,
            ':cat_id' => $data['category_id'],
            ':name' => $data['kpi_name'],
            ':desc' => $data['description'] ?? '',
            ':calc_type' => $data['calculation_type'] ?? 'percentage',
            ':kpi_level' => $data['kpi_level'] ?? 'โรงพยาบาล',
            ':periodicity' => $data['kpi_periodicity'] ?? 'month',
            ':target' => $data['target_value'],
            ':op' => $data['target_operator'] ?? '>=',
            ':unit' => $data['unit'] ?? '',
            ':resp_person' => $data['responsible_person'] ?? '',
            ':resp_unit' => $data['responsible_unit'] ?? '',
            ':num_label' => $data['numerator_label'] ?? null,
            ':den_label' => $data['denominator_label'] ?? null,
            ':multiplier' => $data['multiplier'] ?? null,
            ':fiscal_year' => $data['fiscal_year'] ?? null,
            ':id' => $data['id']
        ]);
    } else {
        $sql = "INSERT INTO kpi_definitions 
                    (code, category_id, name, description, calculation_type, kpi_level, kpi_periodicity, target_value, target_operator, unit, responsible_person, responsible_unit, numerator_label, denominator_label, multiplier, fiscal_year) 
                VALUES 
                    (:code, :cat_id, :name, :desc, :calc_type, :kpi_level, :periodicity, :target, :op, :unit, :resp_person, :resp_unit, :num_label, :den_label, :multiplier, :fiscal_year)";
        $stmt = $pdo2->prepare($sql);
        $stmt->execute([
            ':code' => $data['kpi_code'] ?? null,
            ':cat_id' => $data['category_id'],
            ':name' => $data['kpi_name'],
            ':desc' => $data['description'] ?? '',
            ':calc_type' => $data['calculation_type'] ?? 'percentage',
            ':kpi_level' => $data['kpi_level'] ?? 'โรงพยาบาล',
            ':periodicity' => $data['kpi_periodicity'] ?? 'month',
            ':target' => $data['target_value'],
            ':op' => $data['target_operator'] ?? '>=',
            ':unit' => $data['unit'] ?? '',
            ':resp_person' => $data['responsible_person'] ?? '',
            ':resp_unit' => $data['responsible_unit'] ?? '',
            ':num_label' => $data['numerator_label'] ?? null,
            ':den_label' => $data['denominator_label'] ?? null,
            ':multiplier' => $data['multiplier'] ?? null,
            ':fiscal_year' => $data['fiscal_year'] ?? null
        ]);
    }

    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
