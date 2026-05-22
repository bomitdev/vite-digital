<?php
session_start();


require '../config.php';
require '../auth_utils.php';

// Protect this endpoint with new secure guard
$userData = authGuard();
$user_id = $userData['uid'];


try {
    $sql = "SELECT 
                p.HR_IMAGE,
                CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as FULLNAME,
                hds.HR_DEPARTMENT_SUB_NAME,
                pos.HR_POSITION_NAME,
                hl.HR_LEVEL_NAME,
                p.access_user,
                p.HR_DEPARTMENT_SUB_ID
            FROM hr_person p
            LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
            LEFT JOIN hr_department_sub hds ON p.HR_DEPARTMENT_SUB_ID = hds.HR_DEPARTMENT_SUB_ID
            LEFT JOIN hr_position pos ON p.HR_POSITION_ID = pos.HR_POSITION_ID
            LEFT JOIN hr_level hl ON p.HR_LEVEL_ID = hl.HR_LEVEL_ID
            WHERE p.ID = :id";

    $stmt = $pdo3->prepare($sql);
    $stmt->bindParam(":id", $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Prepare Image
    $image_base64 = '';
    if (!empty($user['HR_IMAGE'])) {
        $image_base64 = 'data:image/jpeg;base64,' . base64_encode($user['HR_IMAGE']);
    }

    echo json_encode([
        "status" => "success",
        "department" => $user['HR_DEPARTMENT_SUB_NAME'] ?? '',
        "department_id" => $user['HR_DEPARTMENT_SUB_ID'] ?? 0,
        "position" => $user['HR_POSITION_NAME'] ?? '',
        "hr_level_name" => $user['HR_LEVEL_NAME'] ?? '',
        "access_user" => $user['access_user'] ?? '',
        "fullname" => $user['FULLNAME'] ?? '',
        "image" => $image_base64
    ]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
