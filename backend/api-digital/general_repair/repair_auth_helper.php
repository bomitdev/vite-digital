<?php
/**
 * General Repair Auth & Permission Helper
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';

function getGeneralRepairUserContext(PDO $pdo2, PDO $pdo3): array
{
    // 1. Try to get user from Bearer JWT token
    $userData = authOptional();
    $uid = 0;
    if ($userData && !empty($userData['uid'])) {
        $uid = (int)$userData['uid'];
    } elseif (isset($_SESSION['user_id'])) {
        $uid = (int)$_SESSION['user_id'];
    }

    // Fallback: check query or body parameters
    if ($uid <= 0) {
        if (!empty($_GET['current_user_id'])) {
            $uid = (int)$_GET['current_user_id'];
        } elseif (!empty($_POST['current_user_id'])) {
            $uid = (int)$_POST['current_user_id'];
        }
    }

    $person = null;
    if ($uid > 0) {
        $stmt = $pdo3->prepare("
            SELECT 
                p.ID,
                CONCAT(COALESCE(pf.HR_PREFIX_NAME, ''), COALESCE(p.HR_FNAME, ''), ' ', COALESCE(p.HR_LNAME, '')) as fullname,
                CONCAT(COALESCE(p.HR_FNAME, ''), ' ', COALESCE(p.HR_LNAME, '')) as fullname_noprefix,
                p.HR_FNAME as fname,
                p.HR_LNAME as lname,
                p.HR_PHONE as phone,
                p.access_user,
                p.HR_DEPARTMENT_SUB_ID as department_sub_id,
                hds.HR_DEPARTMENT_SUB_NAME as department_sub_name,
                hds.HR_DEPARTMENT_ID as department_id,
                d.HR_DEPARTMENT_NAME as department_name,
                pos.HR_POSITION_NAME as position
            FROM hr_person p
            LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
            LEFT JOIN hr_department_sub hds ON p.HR_DEPARTMENT_SUB_ID = hds.HR_DEPARTMENT_SUB_ID
            LEFT JOIN hr_department d ON hds.HR_DEPARTMENT_ID = d.HR_DEPARTMENT_ID
            LEFT JOIN hr_position pos ON p.HR_POSITION_ID = pos.HR_POSITION_ID
            WHERE p.ID = :id
        ");
        $stmt->execute([':id' => $uid]);
        $person = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 2. Check Admin
    $isAdmin = false;
    if ($person) {
        $perms = !empty($person['access_user']) ? explode(':', $person['access_user']) : [];
        if (
            in_array('administrator', $perms, true) ||
            (!empty($person['department_name']) && strpos($person['department_name'], 'กลุ่มงานสุขภาพดิจิทัล') !== false) ||
            (!empty($person['department_sub_name']) && strpos($person['department_sub_name'], 'กลุ่มงานสุขภาพดิจิทัล') !== false) ||
            $uid === 1
        ) {
            $isAdmin = true;
        }
    }

    // 3. Check Technician (from digital.general_repair_technicians)
    $isTechnician = false;
    if ($uid > 0) {
        $stmtTech = $pdo2->prepare("SELECT COUNT(*) FROM general_repair_technicians WHERE person_id = :pid AND is_active = 1");
        $stmtTech->execute([':pid' => $uid]);
        if ((int)$stmtTech->fetchColumn() > 0) {
            $isTechnician = true;
        }
    }

    if (!$isTechnician && $person && !empty($person['fname']) && !empty($person['lname'])) {
        $cleanFname = trim((string)$person['fname']);
        $cleanLname = trim((string)$person['lname']);
        $stmtTechName = $pdo2->prepare("
            SELECT COUNT(*) FROM general_repair_technicians 
            WHERE is_active = 1 
              AND fullname LIKE :fname 
              AND fullname LIKE :lname
        ");
        $stmtTechName->execute([
            ':fname' => '%' . $cleanFname . '%',
            ':lname' => '%' . $cleanLname . '%'
        ]);
        if ((int)$stmtTechName->fetchColumn() > 0) {
            $isTechnician = true;
        }
    }

    $canManage = $isAdmin || $isTechnician;

    return [
        'uid' => $uid,
        'person' => $person,
        'is_admin' => $isAdmin,
        'is_technician' => $isTechnician,
        'can_manage' => $canManage,
        'fullname' => $person['fullname'] ?? '',
        'fullname_noprefix' => $person['fullname_noprefix'] ?? '',
        'department_id' => $person['department_id'] ?? null,
        'department_name' => $person['department_name'] ?? '',
        'department_sub_id' => $person['department_sub_id'] ?? null,
        'department_sub_name' => $person['department_sub_name'] ?? '',
    ];
}

