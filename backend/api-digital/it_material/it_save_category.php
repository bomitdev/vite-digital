<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../cors.php';
require_once __DIR__ . '/../../auth_utils.php';

header("Content-Type: application/json; charset=UTF-8");

$userData = authGuard();

if (!isset($pdo2)) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection missing.']);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true) ?: $_POST;

$id = isset($input['id']) ? intval($input['id']) : 0;
$name = isset($input['name']) ? trim($input['name']) : '';
$prefix = isset($input['prefix']) ? strtoupper(trim($input['prefix'])) : '';

if (empty($name)) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาระบุชื่อประเภท/หมวดหมู่']);
    exit;
}

if (empty($prefix)) {
    if (preg_match('/[A-Za-z]{2,}/', $name, $m)) {
        $prefix = strtoupper(substr($m[0], 0, 3));
    } else {
        $prefix = 'IT';
    }
}

try {
    if ($id > 0) {
        $chkDup = $pdo2->prepare("SELECT id FROM mt_it_categories WHERE name = :name AND id != :id");
        $chkDup->execute([':name' => $name, ':id' => $id]);
        if ($chkDup->fetch()) {
            echo json_encode(['status' => 'error', 'message' => 'ชื่อประเภทนี้มีอยู่ในระบบแล้ว']);
            exit;
        }

        $getOld = $pdo2->prepare("SELECT name FROM mt_it_categories WHERE id = :id");
        $getOld->execute([':id' => $id]);
        $oldCat = $getOld->fetch(PDO::FETCH_ASSOC);

        if (!$oldCat) {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลประเภทที่ต้องการแก้ไข']);
            exit;
        }

        $oldName = $oldCat['name'];

        $upd = $pdo2->prepare("UPDATE mt_it_categories SET name = :name, prefix = :prefix WHERE id = :id");
        $upd->execute([
            ':name' => $name,
            ':prefix' => $prefix,
            ':id' => $id
        ]);

        if ($oldName !== $name) {
            $updMat = $pdo2->prepare("UPDATE mt_it_materials SET type = :newName WHERE type = :oldName");
            $updMat->execute([
                ':newName' => $name,
                ':oldName' => $oldName
            ]);
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'อัปเดตประเภทหมวดหมู่เรียบร้อยแล้ว',
            'data' => [
                'id' => $id,
                'name' => $name,
                'prefix' => $prefix
            ]
        ]);
        exit;
    }

    $stmt = $pdo2->prepare("SELECT id, name, prefix FROM mt_it_categories WHERE name = :name");
    $stmt->execute([':name' => $name]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        if (!empty($prefix) && $existing['prefix'] !== $prefix) {
            $upd = $pdo2->prepare("UPDATE mt_it_categories SET prefix = :prefix WHERE id = :id");
            $upd->execute([':prefix' => $prefix, ':id' => $existing['id']]);
            $existing['prefix'] = $prefix;
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'พบหมวดหมู่นี้ในระบบแล้ว',
            'data' => $existing
        ]);
        exit;
    }

    $insertStmt = $pdo2->prepare("INSERT INTO mt_it_categories (name, prefix) VALUES (:name, :prefix)");
    $insertStmt->execute([
        ':name' => $name,
        ':prefix' => $prefix
    ]);

    $newId = $pdo2->lastInsertId();

    echo json_encode([
        'status' => 'success',
        'message' => 'เพิ่มหมวดหมู่สำเร็จ',
        'data' => [
            'id' => $newId,
            'name' => $name,
            'prefix' => $prefix
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'DB Error: ' . $e->getMessage()
    ]);
}
