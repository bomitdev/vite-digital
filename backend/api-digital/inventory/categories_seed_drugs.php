<?php
require_once '../../config.php';
require_once '../../cors.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true) ?: $_POST;
$warehouse_id = (int)($data['warehouse_id'] ?? 0);

if (!$warehouse_id) {
    // default to pharmacy warehouse if not specified
    $st = $pdo2->query("SELECT id FROM inv_warehouses WHERE name LIKE '%ยา%' OR id = 2 ORDER BY (name LIKE '%ยา%') DESC, id ASC LIMIT 1");
    $wh = $st->fetch(PDO::FETCH_ASSOC);
    $warehouse_id = $wh ? (int)$wh['id'] : 2;
}

$drug_groups = [
    ['code' => '01', 'name' => 'Antidotes'],
    ['code' => '02', 'name' => 'Anti-Infectives'],
    ['code' => '03', 'name' => 'Antineoplastic & Immuno. Drugs'],
    ['code' => '04', 'name' => 'Antiseptics & Disinfectants'],
    ['code' => '05', 'name' => 'Blood & Blood Forming Agents'],
    ['code' => '06', 'name' => 'Cardiovascular Drugs'],
    ['code' => '07', 'name' => 'Drug Acting On Central nervous'],
    ['code' => '08', 'name' => 'Dental & Oral Preparations'],
    ['code' => '09', 'name' => 'Dermatologic Drugs'],
    ['code' => '10', 'name' => 'Diagnosis Agents'],
    ['code' => '11', 'name' => 'Endocrinologic Drugs'],
    ['code' => '12', 'name' => 'EENT Preparations'],
    ['code' => '13', 'name' => 'Gastro-Intestinal Drugs'],
    ['code' => '14', 'name' => 'Genito-urinary & Sex Hormones'],
    ['code' => '15', 'name' => 'Immunological Preparations'],
    ['code' => '16', 'name' => 'Local Anaesthetics'],
    ['code' => '17', 'name' => 'Musculoskeletal System Drugs'],
    ['code' => '18', 'name' => 'Psychotherapeutic Drugs'],
    ['code' => '19', 'name' => 'Radiopharmaceuticals'],
    ['code' => '20', 'name' => 'Drug Acting on Respiratory'],
    ['code' => '21', 'name' => 'Solution Electrolyte'],
    ['code' => '22', 'name' => 'Nutritional Therapeutics'],
    ['code' => '23', 'name' => 'Miscellaneous'],
    ['code' => '24', 'name' => 'ยาสมุนไพร']
];

try {
    $inserted = 0;
    $ins = $pdo2->prepare("INSERT INTO inv_categories (warehouse_id, code, name, active) VALUES (?, ?, ?, 1)");
    $chk = $pdo2->prepare("SELECT id FROM inv_categories WHERE warehouse_id = ? AND code = ?");

    foreach ($drug_groups as $dg) {
        $chk->execute([$warehouse_id, $dg['code']]);
        if (!$chk->fetch()) {
            $ins->execute([$warehouse_id, $dg['code'], $dg['name']]);
            $inserted++;
        }
    }

    require_once __DIR__ . '/inv_log_helper.php';
    recordInvActivityLog($pdo2, [
        'warehouse_id' => $warehouse_id,
        'page_code' => 'settings',
        'page_name' => 'ตั้งค่าระบบ (Master Data)',
        'action' => 'SEED_DRUG_CATEGORIES',
        'target_type' => 'category',
        'target_id' => (string)$warehouse_id,
        'details' => "นำเข้ากลุ่มยามาตรฐานเข้าคลัง #{$warehouse_id} เพิ่มใหม่ {$inserted} รายการ",
        'meta' => ['inserted' => $inserted],
    ], $pdo3 ?? null);

    echo json_encode([
        'status' => 'success',
        'message' => "นำเข้ากลุ่มยาเรียบร้อยแล้ว (เพิ่มใหม่ {$inserted} รายการ)",
        'inserted' => $inserted
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

