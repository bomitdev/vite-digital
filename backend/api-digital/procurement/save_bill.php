<?php
header("Content-Type: application/json; charset=UTF-8");
require __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';

$userData = authGuard();

try {
    $data = $_POST;
    
    if (empty($data['bill_number']) || empty($data['vendor_name'])) {
        throw new Exception("Bill Number and Vendor Name are required.");
    }
    
    $id = !empty($data['id']) && $data['id'] !== 'null' ? $data['id'] : null;
    
    // Helper to upload files
    $uploadFile = function($fileKey) {
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
            $filename = $_FILES[$fileKey]['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) throw new Exception("Invalid file type for " . $fileKey);
            
            $wsRoot = realpath(__DIR__ . '/../../');
            $uploadDir = $wsRoot . '/uploads/procurement/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true)) {
                    throw new Exception("Cannot create upload directory: " . $uploadDir);
                }
            }
            
            $newFilename = uniqid($fileKey . '_') . '.' . $ext;
            $destPath = $uploadDir . $newFilename;
            if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $destPath)) {
                return 'backend/uploads/procurement/' . $newFilename;
            } else {
                throw new Exception("Failed to save file '$newFilename'. Upload dir: $uploadDir | Is writable: " . (is_writable($uploadDir) ? 'yes' : 'no'));
            }
        }
        return null;
    };

    $filePath = $data['file_path'] ?? '';
    $approvalFilePath = $data['approval_file_path'] ?? '';
    $poFilePath = $data['po_file_path'] ?? '';

    $uploadedInvoice = $uploadFile('invoice_file');
    if ($uploadedInvoice) $filePath = $uploadedInvoice;

    $uploadedApproval = $uploadFile('approval_file');
    if ($uploadedApproval) $approvalFilePath = $uploadedApproval;
    
    $uploadedPo = $uploadFile('po_file');
    if ($uploadedPo) $poFilePath = $uploadedPo;
    
    if (!$id) {
        $sql = "INSERT INTO procurement_bills (bill_number, vendor_name, amount, bill_date, notes, file_path, approval_file_path, po_file_path, created_by) 
                VALUES (:bill_number, :vendor_name, :amount, :bill_date, :notes, :file_path, :approval_file_path, :po_file_path, :created_by)";
    } else {
        $sql = "UPDATE procurement_bills SET 
                bill_number = :bill_number, 
                vendor_name = :vendor_name, 
                amount = :amount, 
                bill_date = :bill_date, 
                notes = :notes, 
                file_path = :file_path,
                approval_file_path = :approval_file_path,
                po_file_path = :po_file_path
                WHERE id = :id";
    }
    
    $stmt = $pdo2->prepare($sql);
    $params = [
        ':bill_number' => $data['bill_number'],
        ':vendor_name' => $data['vendor_name'],
        ':amount' => $data['amount'] ?? 0,
        ':bill_date' => $data['bill_date'],
        ':notes' => $data['notes'] ?? '',
        ':file_path' => $filePath,
        ':approval_file_path' => $approvalFilePath,
        ':po_file_path' => $poFilePath
    ];
    
    if (!$id) {
        // Fetch fullname from DB
        $uid = $userData['uid'] ?? 0;
        $stmtName = $pdo3->prepare("SELECT CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as FULLNAME FROM hr_person p LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID WHERE p.ID = ?");
        $stmtName->execute([$uid]);
        $uName = $stmtName->fetchColumn();
        $params[':created_by'] = $uName ?: 'Unknown';
    } else {
        $params[':id'] = $id;
    }
    
    $stmt->execute($params);
    echo json_encode(['status' => 'success', 'message' => 'Bill saved successfully.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
