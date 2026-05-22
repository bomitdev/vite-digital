<?php
header("Content-Type: application/json; charset=UTF-8");
require __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';

// Secure Auth
$userData = authGuard();


try {
    // For file upload, we use $_POST
    $data = $_POST;

    if (empty($data['asset_code']) || empty($data['name'])) {
        throw new Exception("Asset Code and Name are required.");
    }

    // Check duplicate asset_code
    $id = !empty($data['id']) && $data['id'] !== 'null' ? $data['id'] : null;

    if (!$id) {
        $stmt = $pdo2->prepare("SELECT COUNT(*) FROM assets WHERE asset_code = ?");
        $stmt->execute([$data['asset_code']]);
        if ($stmt->fetchColumn() > 0) throw new Exception("มีเลขครุภัณฑ์นี้อยู่ในระบบแล้ว");
    } else {
        $stmt = $pdo2->prepare("SELECT COUNT(*) FROM assets WHERE asset_code = ? AND id != ?");
        $stmt->execute([$data['asset_code'], $id]);
        if ($stmt->fetchColumn() > 0) throw new Exception("มีเลขครุภัณฑ์นี้อยู่ในระบบแล้ว");
    }

    // Image Upload Handling
    $imagePath = $data['image_path'] ?? '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) throw new Exception("Invalid file type.");

        // Upload to: backend/uploads/assets/
        $wsRoot = realpath(__DIR__ . '/../../'); // backend root
        $uploadDir = $wsRoot . '/uploads/assets/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $newFilename = uniqid('asset_') . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newFilename)) {
            // Save path accessible by web
            // Assuming web root is /vue-app/vite-digital/backend/
            $imagePath = 'backend/uploads/assets/' . $newFilename;
        }
    }

    if (!$id) {
        $sql = "INSERT INTO assets (asset_code, name, type, brand, model, size, unit, acquisition_method, source, serial_number, spec_cpu, spec_ram, spec_storage, os, status, purchase_date, warranty_expire_date, price, location, responsible_person, notes, image_path)
VALUES (:code, :name, :type, :brand, :model, :size, :unit, :acq_method, :source, :serial, :cpu, :ram, :storage, :os, :status, :purchase, :warranty, :price, :location, :person, :notes, :image)";
    } else {
        $sql = "UPDATE assets SET
asset_code = :code, name = :name, type = :type, brand = :brand, model = :model, size = :size, unit = :unit,
acquisition_method = :acq_method, source = :source,
serial_number = :serial,
spec_cpu = :cpu, spec_ram = :ram, spec_storage = :storage, os = :os, status = :status,
purchase_date = :purchase, warranty_expire_date = :warranty, price = :price, location = :location,
responsible_person = :person, notes = :notes, image_path = :image
WHERE id = :id";
    }

    $stmt = $pdo2->prepare($sql);

    $params = [
        ':code' => $data['asset_code'],
        ':name' => $data['name'],
        ':type' => $data['type'] ?? '',
        ':brand' => $data['brand'] ?? '',
        ':model' => $data['model'] ?? '',
        ':size' => $data['size'] ?? '',
        ':unit' => $data['unit'] ?? '',
        ':acq_method' => $data['acquisition_method'] ?? '',
        ':source' => $data['source'] ?? '',
        ':serial' => $data['serial_number'] ?? '',
        ':cpu' => $data['spec_cpu'] ?? '',
        ':ram' => $data['spec_ram'] ?? '',
        ':storage' => $data['spec_storage'] ?? '',
        ':os' => $data['os'] ?? '',
        ':status' => $data['status'] ?? 'Active',
        ':purchase' => !empty($data['purchase_date']) ? $data['purchase_date'] : null,
        ':warranty' => !empty($data['warranty_expire_date']) ? $data['warranty_expire_date'] : null,
        ':price' => !empty($data['price']) ? $data['price'] : null,
        ':location' => $data['location'] ?? '',
        ':person' => $data['responsible_person'] ?? '',
        ':notes' => $data['notes'] ?? '',
        ':image' => $imagePath
    ];

    if ($id) {
        $params[':id'] = $id;
    }

    $stmt->execute($params);

    echo json_encode(['status' => 'success', 'message' => 'Asset saved successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
