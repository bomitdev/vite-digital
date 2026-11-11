<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';

header("Content-Type: application/json; charset=utf-8");

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $table = trim($_GET['table'] ?? 'all');

        $fetchDosageForms = function() use ($pdo2) {
            return $pdo2->query("
                SELECT df.*, 
                       (SELECT COUNT(*) FROM inv_items i WHERE i.dosage_form_id = df.id) as item_count
                FROM inv_dosage_forms df
                ORDER BY item_count DESC, df.code ASC
            ")->fetchAll(PDO::FETCH_ASSOC);
        };

        $fetchEdLists = function() use ($pdo2) {
            return $pdo2->query("
                SELECT ed.*, 
                       (SELECT COUNT(*) FROM inv_items i WHERE i.ed_list_id = ed.id) as item_count
                FROM inv_ed_lists ed
                ORDER BY ed.sort_order ASC, ed.code ASC
            ")->fetchAll(PDO::FETCH_ASSOC);
        };

        $fetchProductCategories = function() use ($pdo2) {
            return $pdo2->query("
                SELECT pc.*, 
                       (SELECT COUNT(*) FROM inv_items i WHERE i.product_cat_id = pc.id) as item_count
                FROM inv_product_categories pc
                ORDER BY CAST(pc.code AS UNSIGNED) ASC, pc.code ASC
            ")->fetchAll(PDO::FETCH_ASSOC);
        };

        if ($table === 'dosage_forms') {
            echo json_encode(['status' => 'success', 'data' => $fetchDosageForms()], JSON_UNESCAPED_UNICODE);
        } elseif ($table === 'ed_lists') {
            echo json_encode(['status' => 'success', 'data' => $fetchEdLists()], JSON_UNESCAPED_UNICODE);
        } elseif ($table === 'product_categories') {
            echo json_encode(['status' => 'success', 'data' => $fetchProductCategories()], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'status' => 'success',
                'dosage_forms' => $fetchDosageForms(),
                'ed_lists' => $fetchEdLists(),
                'product_categories' => $fetchProductCategories()
            ], JSON_UNESCAPED_UNICODE);
        }

    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true) ?: $_POST;
        $table = trim($data['table'] ?? '');
        $code = trim($data['code'] ?? '');
        $name = trim($data['name'] ?? '');
        $active = isset($data['active']) ? (int)$data['active'] : 1;

        if (!$code || !$name) {
            echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกรหัสและชื่อรายการ'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($table === 'dosage_forms') {
            $nameEn = trim($data['name_en'] ?? '');
            $chk = $pdo2->prepare("SELECT id FROM inv_dosage_forms WHERE code = ?");
            $chk->execute([$code]);
            if ($chk->fetch()) {
                echo json_encode(['status' => 'error', 'message' => "รหัสรูปแบบยา ({$code}) มีอยู่ในระบบแล้ว"], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $stmt = $pdo2->prepare("INSERT INTO inv_dosage_forms (code, name, name_en, active) VALUES (?, ?, ?, ?)");
            $stmt->execute([$code, $name, $nameEn, $active]);
            echo json_encode(['status' => 'success', 'message' => 'เพิ่มรูปแบบยาเรียบร้อยแล้ว', 'id' => (int)$pdo2->lastInsertId()], JSON_UNESCAPED_UNICODE);

        } elseif ($table === 'ed_lists') {
            $desc = trim($data['description'] ?? '');
            $sort = (int)($data['sort_order'] ?? 0);
            $chk = $pdo2->prepare("SELECT id FROM inv_ed_lists WHERE code = ?");
            $chk->execute([$code]);
            if ($chk->fetch()) {
                echo json_encode(['status' => 'error', 'message' => "รหัสบัญชีย่อย ({$code}) มีอยู่ในระบบแล้ว"], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $stmt = $pdo2->prepare("INSERT INTO inv_ed_lists (code, name, description, sort_order, active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$code, $name, $desc, $sort, $active]);
            echo json_encode(['status' => 'success', 'message' => 'เพิ่มบัญชียาหลักย่อยเรียบร้อยแล้ว', 'id' => (int)$pdo2->lastInsertId()], JSON_UNESCAPED_UNICODE);

        } elseif ($table === 'product_categories') {
            $desc = trim($data['description'] ?? '');
            $chk = $pdo2->prepare("SELECT id FROM inv_product_categories WHERE code = ?");
            $chk->execute([$code]);
            if ($chk->fetch()) {
                echo json_encode(['status' => 'error', 'message' => "รหัสหมวด อย. ({$code}) มีอยู่ในระบบแล้ว"], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $stmt = $pdo2->prepare("INSERT INTO inv_product_categories (code, name, description, active) VALUES (?, ?, ?, ?)");
            $stmt->execute([$code, $name, $desc, $active]);
            echo json_encode(['status' => 'success', 'message' => 'เพิ่มหมวดผลิตภัณฑ์ อย. เรียบร้อยแล้ว', 'id' => (int)$pdo2->lastInsertId()], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ไม่ระบุตารางที่ต้องการจัดการ (table)'], JSON_UNESCAPED_UNICODE);
        }

    } elseif ($method === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true) ?: [];
        $table = trim($data['table'] ?? '');
        $id = (int)($data['id'] ?? 0);
        $code = trim($data['code'] ?? '');
        $name = trim($data['name'] ?? '');
        $active = isset($data['active']) ? (int)$data['active'] : 1;

        if (!$id || !$code || !$name) {
            echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน (ต้องระบุ ID, รหัส และชื่อ)'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($table === 'dosage_forms') {
            $nameEn = trim($data['name_en'] ?? '');
            $chk = $pdo2->prepare("SELECT id FROM inv_dosage_forms WHERE code = ? AND id != ?");
            $chk->execute([$code, $id]);
            if ($chk->fetch()) {
                echo json_encode(['status' => 'error', 'message' => "รหัสรูปแบบยา ({$code}) ซ้ำกับรายการอื่น"], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $stmt = $pdo2->prepare("UPDATE inv_dosage_forms SET code = ?, name = ?, name_en = ?, active = ? WHERE id = ?");
            $stmt->execute([$code, $name, $nameEn, $active, $id]);
            // Sync legacy dosage_form column
            $pdo2->prepare("UPDATE inv_items SET dosage_form = ? WHERE dosage_form_id = ?")->execute([$code, $id]);
            echo json_encode(['status' => 'success', 'message' => 'อัปเดตรูปแบบยาเรียบร้อยแล้ว'], JSON_UNESCAPED_UNICODE);

        } elseif ($table === 'ed_lists') {
            $desc = trim($data['description'] ?? '');
            $sort = (int)($data['sort_order'] ?? 0);
            $chk = $pdo2->prepare("SELECT id FROM inv_ed_lists WHERE code = ? AND id != ?");
            $chk->execute([$code, $id]);
            if ($chk->fetch()) {
                echo json_encode(['status' => 'error', 'message' => "รหัสบัญชีย่อย ({$code}) ซ้ำกับรายการอื่น"], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $stmt = $pdo2->prepare("UPDATE inv_ed_lists SET code = ?, name = ?, description = ?, sort_order = ?, active = ? WHERE id = ?");
            $stmt->execute([$code, $name, $desc, $sort, $active, $id]);
            // Sync legacy ed_sub_list column
            $pdo2->prepare("UPDATE inv_items SET ed_sub_list = ? WHERE ed_list_id = ?")->execute([$code, $id]);
            echo json_encode(['status' => 'success', 'message' => 'อัปเดตบัญชียาหลักย่อยเรียบร้อยแล้ว'], JSON_UNESCAPED_UNICODE);

        } elseif ($table === 'product_categories') {
            $desc = trim($data['description'] ?? '');
            $chk = $pdo2->prepare("SELECT id FROM inv_product_categories WHERE code = ? AND id != ?");
            $chk->execute([$code, $id]);
            if ($chk->fetch()) {
                echo json_encode(['status' => 'error', 'message' => "รหัสหมวด อย. ({$code}) ซ้ำกับรายการอื่น"], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $stmt = $pdo2->prepare("UPDATE inv_product_categories SET code = ?, name = ?, description = ?, active = ? WHERE id = ?");
            $stmt->execute([$code, $name, $desc, $active, $id]);
            // Sync legacy product_cat column
            $pdo2->prepare("UPDATE inv_items SET product_cat = ? WHERE product_cat_id = ?")->execute([$name, $id]);
            echo json_encode(['status' => 'success', 'message' => 'อัปเดตหมวดผลิตภัณฑ์ อย. เรียบร้อยแล้ว'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ไม่ระบุตารางที่ต้องการแก้ไข'], JSON_UNESCAPED_UNICODE);
        }

    } elseif ($method === 'DELETE') {
        $table = trim($_GET['table'] ?? '');
        $id = (int)($_GET['id'] ?? 0);

        if (!$id || !$table) {
            echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($table === 'dosage_forms') {
            $cnt = $pdo2->prepare("SELECT COUNT(*) FROM inv_items WHERE dosage_form_id = ?");
            $cnt->execute([$id]);
            if ((int)$cnt->fetchColumn() > 0) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบได้ เนื่องจากมีรายการยาที่ใช้รูปแบบยานี้อยู่'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $pdo2->prepare("DELETE FROM inv_dosage_forms WHERE id = ?")->execute([$id]);
            echo json_encode(['status' => 'success', 'message' => 'ลบรูปแบบยาเรียบร้อยแล้ว'], JSON_UNESCAPED_UNICODE);

        } elseif ($table === 'ed_lists') {
            $cnt = $pdo2->prepare("SELECT COUNT(*) FROM inv_items WHERE ed_list_id = ?");
            $cnt->execute([$id]);
            if ((int)$cnt->fetchColumn() > 0) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบได้ เนื่องจากมีรายการยาที่ผูกกับบัญชีย่อยนี้อยู่'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $pdo2->prepare("DELETE FROM inv_ed_lists WHERE id = ?")->execute([$id]);
            echo json_encode(['status' => 'success', 'message' => 'ลบบัญชียาหลักย่อยเรียบร้อยแล้ว'], JSON_UNESCAPED_UNICODE);

        } elseif ($table === 'product_categories') {
            $cnt = $pdo2->prepare("SELECT COUNT(*) FROM inv_items WHERE product_cat_id = ?");
            $cnt->execute([$id]);
            if ((int)$cnt->fetchColumn() > 0) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถลบได้ เนื่องจากมีรายการยาที่ผูกกับหมวดหมู่ อย. นี้อยู่'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $pdo2->prepare("DELETE FROM inv_product_categories WHERE id = ?")->execute([$id]);
            echo json_encode(['status' => 'success', 'message' => 'ลบหมวดผลิตภัณฑ์ อย. เรียบร้อยแล้ว'], JSON_UNESCAPED_UNICODE);
        }
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

