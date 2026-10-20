<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config.php';

try {
    if (!isset($pdo1)) {
        throw new Exception("HOSxP Database connection missing.");
    }

    $an = isset($_GET['an']) ? trim($_GET['an']) : '';
    $requestedHeadId = isset($_GET['assessment_head_id']) ? (int)$_GET['assessment_head_id'] : null;

    if (empty($an)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing parameter an'
        ]);
        exit;
    }

    // 1. Get Patient Info
    $stmtPt = $pdo1->prepare("
        SELECT 
            a.an, a.hn, CONCAT(p.pname, p.fname, ' ', p.lname) as pt_name,
            a.regdate, a.dchdate, COALESCE(w.name, 'ไม่ระบุ') as ward_name,
            a.age_y, a.sex
        FROM an_stat a
        JOIN patient p ON p.hn = a.hn
        LEFT JOIN ward w ON w.ward = a.ward
        WHERE a.an = :an
    ");
    $stmtPt->execute([':an' => $an]);
    $patient = $stmtPt->fetch(PDO::FETCH_ASSOC);

    // 2. Get all HAD assessment heads for this AN
    $sqlHeads = "
        SELECT 
            h.assessment_head_id,
            h.assessment_head_datetime,
            h.assessment_head_staff,
            f.assessment_form_id,
            f.assessment_form_name,
            f.assessment_form_table
        FROM assessment_head h
        JOIN assessment_form f ON f.assessment_form_id = h.assessment_form_id
        WHERE h.an = :an AND f.assessment_form_name LIKE '[HAD]%'
        ORDER BY h.assessment_head_datetime ASC
    ";
    $stmtHeads = $pdo1->prepare($sqlHeads);
    $stmtHeads->execute([':an' => $an]);
    $heads = $stmtHeads->fetchAll(PDO::FETCH_ASSOC);

    $assessments = [];

    foreach ($heads as $h) {
        $table = $h['assessment_form_table'];
        $hId = $h['assessment_head_id'];
        
        $detail = null;
        if (!empty($table)) {
            try {
                $stmtData = $pdo1->prepare("SELECT * FROM $table WHERE assessment_head_id = :hid");
                $stmtData->execute([':hid' => $hId]);
                $detail = $stmtData->fetch(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $detail = null;
            }
        }

        // Format structured details if detail exists
        $structured = null;
        if ($detail) {
            // Doctors and nurses
            $orderInfo = [
                'order_text' => $detail['asm_tx100'] ?? ($detail['asm_dx'] ?? ''),
                'order_date' => $detail['asm_date100'] ?? '',
                'doctor' => $detail['asm_staff100'] ?? '',
                'order_receiver' => $detail['asm_staff101'] ?? '',
                'drug_preparer' => $detail['asm_staff102'] ?? '',
                'drug_administrator' => $detail['asm_staff103'] ?? '',
                'channel_hosxp' => ($detail['asm_chk100'] ?? '') === 'Y',
                'channel_prescription' => ($detail['asm_chk101'] ?? '') === 'Y',
                'channel_phone' => ($detail['asm_chk102'] ?? '') === 'Y',
                'channel_other' => ($detail['asm_chk103'] ?? '') === 'Y'
            ];

            // Vital signs / Monitoring checks (Standard HOSxP HAD forms have 8 hourly monitoring slots)
            $monitoring = [];
            for ($i = 1; $i <= 8; $i++) {
                $time = isset($detail["asm_time$i"]) && $detail["asm_time$i"] !== '00:00:00' ? $detail["asm_time$i"] : ($detail["asm_time$i"] ?? null);
                $date = $detail["asm_date$i"] ?? null;
                $bp = $detail["asm_tx$i"] ?? null;
                $hr = $detail["asm_tx" . ($i + 9)] ?? null; // tx10..tx17
                $staff = $detail["asm_staff$i"] ?? null;
                $remark = $detail["asm_tx" . ($i + 45)] ?? null; // tx46..tx53

                if ((!empty($time) && $time !== '00:00:00') || !empty($bp) || !empty($hr) || (!empty($staff) && $staff !== '-')) {
                    $monitoring[] = [
                        'round' => $i,
                        'date' => $date,
                        'time' => $time,
                        'bp' => $bp ?: '-',
                        'hr' => $hr ?: '-',
                        'staff' => $staff ?: '-',
                        'remark' => $remark ?: '-'
                    ];
                }
            }

            // Safety checklist responses
            $checklists = [];
            $checkMap = [
                'asm_chk19' => 'ตรวจสอบชื่อ-สกุล ถูกต้องตามมาตรฐาน 2 ตัวบ่งชี้',
                'asm_chk20' => 'ตรวจสอบประวัติการแพ้ยาและข้อห้ามใช้',
                'asm_chk21' => 'ตรวจสอบความถูกต้องของคำสั่งแพทย์ ขนาดยา และวิธีบริหารยา',
                'asm_chk22' => 'Double check โดยพยาบาล 2 ท่าน ก่อนให้ยา',
                'asm_chk23' => 'ติดป้ายเตือนยาความเสี่ยงสูง (HAD Label) ชัดเจน',
                'asm_chk24' => 'บันทึกอัตราการหยดและการทำงานของ Infusion Pump',
                'asm_chk25' => 'ประเมินข้อบ่งใช้และติดตามผลข้างเคียงขณะให้ยา',
                'asm_chk31' => 'สังเกตอาการไม่พึงประสงค์จากการใช้ยา',
                'asm_chk37' => 'สภาพทั่วไปของผู้ป่วย: ปกติ',
                'asm_chk38' => 'สภาพทั่วไปของผู้ป่วย: ผิดปกติ'
            ];
            foreach ($checkMap as $k => $label) {
                if (isset($detail[$k]) && ($detail[$k] === 'Y' || $detail[$k] === 'N')) {
                    $checklists[] = [
                        'field' => $k,
                        'label' => $label,
                        'checked' => $detail[$k] === 'Y'
                    ];
                }
            }

            // Filter out empty raw fields for reference
            $rawFilled = [];
            foreach ($detail as $k => $v) {
                if ($v !== null && $v !== '' && !in_array($k, ['asm_tx70', 'asm_tx69'])) {
                    $rawFilled[$k] = $v;
                }
            }

            $structured = [
                'order' => $orderInfo,
                'monitoring' => $monitoring,
                'checklists' => $checklists,
                'raw_filled' => $rawFilled
            ];
        }

        $assessments[] = [
            'assessment_head_id' => (int)$h['assessment_head_id'],
            'datetime' => $h['assessment_head_datetime'],
            'staff' => $h['assessment_head_staff'],
            'form_id' => (int)$h['assessment_form_id'],
            'form_name' => $h['assessment_form_name'],
            'table_name' => $h['assessment_form_table'],
            'data' => $structured
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'patient' => $patient,
            'total_assessments' => count($assessments),
            'assessments' => $assessments
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
