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

// Maps will be built from DB

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

    // Pre-fetch kpi_levels for mapping
    $stmtLevels = $pdo2->query("SELECT id, name FROM kpi_levels");
    $validLevelsMap = [];
    while ($row = $stmtLevels->fetch(PDO::FETCH_ASSOC)) {
        $validLevelsMap[trim(mb_strtolower($row['name']))] = $row['id'];
    }

    // Pre-fetch kpi_periodicities
    $stmtPeriod = $pdo2->query("SELECT id, code FROM kpi_periodicities");
    $validPeriodMap = [];
    $validPeriodCodeMap = [];
    while ($row = $stmtPeriod->fetch(PDO::FETCH_ASSOC)) {
        $validPeriodMap[$row['id']] = $row['code'];
        $validPeriodCodeMap[$row['code']] = $row['code'];
    }

    // Pre-fetch kpi_calculation_types
    $stmtCalc = $pdo2->query("SELECT id, code FROM kpi_calculation_types");
    $validCalcMap = [];
    $validCalcCodeMap = [];
    while ($row = $stmtCalc->fetch(PDO::FETCH_ASSOC)) {
        $validCalcMap[$row['id']] = $row['code'];
        $validCalcCodeMap[$row['code']] = $row['code'];
    }

    // Pre-fetch kpi_units
    $stmtUnit = $pdo2->query("SELECT id, name FROM kpi_units");
    $validUnitMap = [];
    $validUnitNameMap = [];
    while ($row = $stmtUnit->fetch(PDO::FETCH_ASSOC)) {
        $validUnitMap[$row['id']] = $row['name'];
        $validUnitNameMap[trim(mb_strtolower($row['name']))] = $row['name'];
    }

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
        
        $calc_type_input = $item['calculation_type'] ?? '';
        $calc_type = 'percentage';
        if (!empty($calc_type_input)) {
            if (isset($validCalcMap[$calc_type_input])) {
                $calc_type = $validCalcMap[$calc_type_input];
            } elseif (isset($validCalcCodeMap[$calc_type_input])) {
                $calc_type = $validCalcCodeMap[$calc_type_input];
            } else {
                $errors[] = "แถว $rowNum: ประเภทการคำนวณไม่ถูกต้อง ('$calc_type_input')";
                continue;
            }
        }

        $kpi_level_input = $item['kpi_level'] ?? '';
        $kpi_level_ids = [];
        if (!empty($kpi_level_input)) {
            $parts = explode(',', $kpi_level_input);
            foreach ($parts as $part) {
                $cleaned = trim($part);
                if (empty($cleaned)) continue;
                $key = mb_strtolower($cleaned);
                if (isset($validLevelsMap[$key])) {
                    $kpi_level_ids[] = $validLevelsMap[$key];
                } elseif (is_numeric($cleaned) && in_array((int)$cleaned, $validLevelsMap, true)) {
                    $kpi_level_ids[] = (int)$cleaned;
                } elseif (is_numeric($cleaned)) {
                    // Accept anyway if they forced a numeric ID
                    $kpi_level_ids[] = (int)$cleaned;
                }
            }
        }
        if (empty($kpi_level_ids)) {
            // We will handle default fallback during insert/update logic
        } else {
            $kpi_level = implode(',', array_unique($kpi_level_ids));
        }

        $periodicity_input = $item['kpi_periodicity'] ?? '';
        $periodicity = 'month';
        if (!empty($periodicity_input)) {
            if (isset($validPeriodMap[$periodicity_input])) {
                $periodicity = $validPeriodMap[$periodicity_input];
            } elseif (isset($validPeriodCodeMap[$periodicity_input])) {
                $periodicity = $validPeriodCodeMap[$periodicity_input];
            } else {
                $errors[] = "แถว $rowNum: รอบการประเมินไม่ถูกต้อง ('$periodicity_input')";
                continue;
            }
        }

        $target = $item['target_value'] ?? 0;
        if (!is_numeric($target)) {
            $errors[] = "แถว $rowNum: ค่าเป้าหมายต้องเป็นตัวเลข";
            continue;
        }

        $op = $item['target_operator'] ?? '>=';
        
        $unit_input = $item['unit'] ?? '';
        $unit = '%';
        if (!empty($unit_input)) {
            $key = mb_strtolower(trim($unit_input));
            if (isset($validUnitMap[$unit_input])) {
                $unit = $validUnitMap[$unit_input];
            } elseif (isset($validUnitNameMap[$key])) {
                $unit = $validUnitNameMap[$key];
            } else {
                $unit = $unit_input; // Allow custom string
            }
        }

        $resp_person = $item['responsible_person'] ?? '';
        $resp_unit = $item['responsible_unit'] ?? '';
        $level_codes_raw = $item['level_codes'] ?? '';
        
        $clean_level_codes = null;
        if (!empty($level_codes_raw)) {
            $decoded = json_decode($level_codes_raw, true);
            if (is_array($decoded)) {
                $cleaned = [];
                foreach ($decoded as $k => $v) {
                    if ($v !== null && trim($v) !== '') {
                        $cleaned[(string)$k] = trim($v);
                    }
                }
                if (!empty($cleaned)) {
                    $clean_level_codes = json_encode($cleaned, JSON_FORCE_OBJECT);
                }
            }
        }

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
                // Preserve existing code if not provided in the import
                if (empty($code)) {
                    $getCodeStmt = $pdo2->prepare("SELECT code FROM kpi_definitions WHERE id = ?");
                    $getCodeStmt->execute([$existingId]);
                    $existingCode = $getCodeStmt->fetchColumn();
                    if (!empty($existingCode)) {
                        $code = $existingCode;
                    }
                }
                
                // Preserve existing level if not provided
                if (empty($kpi_level)) {
                    $getLevelStmt = $pdo2->prepare("SELECT kpi_level FROM kpi_definitions WHERE id = ?");
                    $getLevelStmt->execute([$existingId]);
                    $existingLevel = $getLevelStmt->fetchColumn();
                    if (!empty($existingLevel)) {
                        $kpi_level = $existingLevel;
                    } else {
                        // fallback to default if absolutely empty
                        $key = mb_strtolower('โรงพยาบาล');
                        if (isset($validLevelsMap[$key])) {
                            $kpi_level = $validLevelsMap[$key];
                        } else {
                            $kpi_level = '4'; // fallback ID
                        }
                    }
                }

                // Update
                $sql = "UPDATE kpi_definitions SET 
                        code = :code, category_id = :cat_id, description = :desc, 
                        calculation_type = :calc_type, kpi_level = :kpi_level, 
                        kpi_periodicity = :periodicity, target_value = :target, 
                        target_operator = :op, unit = :unit, responsible_person = :resp_person, 
                        responsible_unit = :resp_unit, level_codes = :level_codes
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
                    ':level_codes' => $clean_level_codes,
                    ':id' => $existingId
                ]);
                $updatedCount++;
            } else {
                // Insert
                if (empty($kpi_level)) {
                    $key = mb_strtolower('โรงพยาบาล');
                    if (isset($validLevelsMap[$key])) {
                        $kpi_level = $validLevelsMap[$key];
                    } else {
                        $kpi_level = '4';
                    }
                }
                
                $sql = "INSERT INTO kpi_definitions 
                            (code, category_id, name, description, calculation_type, kpi_level, kpi_periodicity, target_value, target_operator, unit, responsible_person, responsible_unit, fiscal_year, level_codes) 
                        VALUES 
                            (:code, :cat_id, :name, :desc, :calc_type, :kpi_level, :periodicity, :target, :op, :unit, :resp_person, :resp_unit, :fiscal_year, :level_codes)";
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
                    ':fiscal_year' => $fiscal_year,
                    ':level_codes' => $clean_level_codes
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
