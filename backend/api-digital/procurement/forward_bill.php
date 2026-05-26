<?php
header("Content-Type: application/json; charset=UTF-8");
require __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';

$userData = authGuard();

try {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['id'])) {
        throw new Exception("Bill ID is required.");
    }
    
    $id = $data['id'];
    $uid = $userData['uid'] ?? 0;
    $stmtName = $pdo3->prepare("SELECT CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as FULLNAME FROM hr_person p LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID WHERE p.ID = ?");
    $stmtName->execute([$uid]);
    $uName = $stmtName->fetchColumn();
    $fullname = $uName ?: 'Unknown';
    
    $sql = "UPDATE procurement_bills SET status = 'Forwarded', forwarded_by = :user, forwarded_at = NOW() WHERE id = :id AND status = 'Draft'";
    $stmt = $pdo2->prepare($sql);
    $stmt->execute([':user' => $fullname, ':id' => $id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("Failed to forward bill. It may have already been forwarded.");
    }
    
    echo json_encode(['status' => 'success', 'message' => 'Bill forwarded successfully.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
