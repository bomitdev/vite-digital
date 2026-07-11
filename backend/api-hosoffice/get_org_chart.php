<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../config.php';

/** @var PDO $pdo3 */

$dept_id_filter = isset($_GET['dept_id']) ? $_GET['dept_id'] : null;

try {
    // 1. Get Top Level Head (Director)
    $sql_head = "SELECT 
                    p.ID,
                    CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as name,
                    pos.HR_POSITION_NAME as role,
                    p.HR_IMAGE,
                    d.HR_DEPARTMENT_NAME as group_name
                FROM hr_person p
                LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
                LEFT JOIN hr_position pos ON p.HR_POSITION_ID = pos.HR_POSITION_ID
                LEFT JOIN hr_department d ON p.HR_DEPARTMENT_ID = d.HR_DEPARTMENT_ID
                WHERE pos.HR_POSITION_NAME LIKE '%ผู้อำนวยการโรงพยาบาล%'
                AND p.HR_STATUS_ID = '01'
                LIMIT 1";
    
    $stmt_head = $pdo3->prepare($sql_head);
    $stmt_head->execute();
    $head = $stmt_head->fetch(PDO::FETCH_ASSOC);

    if ($head) {
        $head['image'] = $head['HR_IMAGE'] ? 'data:image/jpeg;base64,' . base64_encode($head['HR_IMAGE']) : null;
        unset($head['HR_IMAGE']);
    } else {
        $head = ['name' => 'ไม่พบข้อมูลผู้อำนวยการ', 'role' => 'ผู้อำนวยการโรงพยาบาล', 'image' => null, 'group_name' => ''];
    }

    // 2. Get Main Departments (กลุ่มงาน)
    $sql_groups = "SELECT HR_DEPARTMENT_ID, HR_DEPARTMENT_NAME, LEADER_HR_ID 
                   FROM hr_department 
                   WHERE ACTIVE = 'True'";
    
    if ($dept_id_filter) {
        $sql_groups .= " AND HR_DEPARTMENT_ID = :dept_id";
    }
    
    $sql_groups .= " ORDER BY HR_DEPARTMENT_NAME ASC";
    
    $stmt_groups = $pdo3->prepare($sql_groups);
    if ($dept_id_filter) {
        $stmt_groups->execute(['dept_id' => $dept_id_filter]);
    } else {
        $stmt_groups->execute();
    }
    $groups_raw = $stmt_groups->fetchAll(PDO::FETCH_ASSOC);

    $groups = [];
    foreach ($groups_raw as $group) {
        // Get Group Leader info
        $group_head = null;
        if ($group['LEADER_HR_ID']) {
            $sql_g_head = "SELECT 
                            p.ID,
                            CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as name,
                            pos.HR_POSITION_NAME as role,
                            p.HR_IMAGE
                          FROM hr_person p
                          LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
                          LEFT JOIN hr_position pos ON p.HR_POSITION_ID = pos.HR_POSITION_ID
                          WHERE p.ID = :id";
            $stmt_g_head = $pdo3->prepare($sql_g_head);
            $stmt_g_head->execute(['id' => $group['LEADER_HR_ID']]);
            $group_head = $stmt_g_head->fetch(PDO::FETCH_ASSOC);
            if ($group_head) {
                $group_head['image'] = $group_head['HR_IMAGE'] ? 'data:image/jpeg;base64,' . base64_encode($group_head['HR_IMAGE']) : null;
                unset($group_head['HR_IMAGE']);
            }
        }
        if (!$group_head) {
            $group_head = ['name' => 'หัวหน้ากลุ่มงาน', 'role' => $group['HR_DEPARTMENT_NAME'], 'image' => null];
        }

        // 3. Get Sub Departments (งาน/แผนก) under this group
        $sql_subs = "SELECT HR_DEPARTMENT_SUB_ID, HR_DEPARTMENT_SUB_NAME, LEADER_HR_ID 
                     FROM hr_department_sub 
                     WHERE HR_DEPARTMENT_ID = :group_id AND ACTIVE = 'True' 
                     ORDER BY HR_DEPARTMENT_SUB_NAME ASC";
        $stmt_subs = $pdo3->prepare($sql_subs);
        $stmt_subs->execute(['group_id' => $group['HR_DEPARTMENT_ID']]);
        $subs_raw = $stmt_subs->fetchAll(PDO::FETCH_ASSOC);

        $subs = [];
        foreach ($subs_raw as $sub) {
            // Get Sub Leader
            $sub_head = null;
            if ($sub['LEADER_HR_ID']) {
                $sql_s_head = "SELECT 
                                p.ID,
                                CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as name,
                                pos.HR_POSITION_NAME as role,
                                p.HR_IMAGE
                              FROM hr_person p
                              LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
                              LEFT JOIN hr_position pos ON p.HR_POSITION_ID = pos.HR_POSITION_ID
                              WHERE p.ID = :id";
                $stmt_s_head = $pdo3->prepare($sql_s_head);
                $stmt_s_head->execute(['id' => $sub['LEADER_HR_ID']]);
                $sub_head = $stmt_s_head->fetch(PDO::FETCH_ASSOC);
                if ($sub_head) {
                    $sub_head['image'] = $sub_head['HR_IMAGE'] ? 'data:image/jpeg;base64,' . base64_encode($sub_head['HR_IMAGE']) : null;
                    unset($sub_head['HR_IMAGE']);
                }
            }
            if (!$sub_head) {
                $sub_head = ['name' => 'หัวหน้างาน', 'role' => $sub['HR_DEPARTMENT_SUB_NAME'], 'image' => null];
            }

            // Get Staff
            $sql_staff = "SELECT 
                            p.ID,
                            CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as name,
                            pos.HR_POSITION_NAME as role,
                            p.HR_IMAGE
                          FROM hr_person p
                          LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
                          LEFT JOIN hr_position pos ON p.HR_POSITION_ID = pos.HR_POSITION_ID
                          WHERE p.HR_DEPARTMENT_SUB_ID = :sub_id 
                          AND p.ID != :head_id 
                          AND p.ID != :group_head_id
                          AND p.HR_STATUS_ID = '01'
                          ORDER BY p.HR_FNAME ASC";
            $stmt_staff = $pdo3->prepare($sql_staff);
            $stmt_staff->execute([
                'sub_id' => $sub['HR_DEPARTMENT_SUB_ID'],
                'head_id' => $sub['LEADER_HR_ID'] ?? 0,
                'group_head_id' => $group['LEADER_HR_ID'] ?? 0
            ]);
            $staff_list = $stmt_staff->fetchAll(PDO::FETCH_ASSOC);
            foreach ($staff_list as &$staff) {
                $staff['image'] = $staff['HR_IMAGE'] ? 'data:image/jpeg;base64,' . base64_encode($staff['HR_IMAGE']) : null;
                unset($staff['HR_IMAGE']);
            }

            $subs[] = [
                'id' => $sub['HR_DEPARTMENT_SUB_ID'],
                'name' => $sub['HR_DEPARTMENT_SUB_NAME'],
                'head' => $sub_head,
                'staff' => $staff_list
            ];
        }

        // Get Direct Staff for Group
        $sql_group_staff = "SELECT 
                        p.ID,
                        CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as name,
                        pos.HR_POSITION_NAME as role,
                        p.HR_IMAGE
                      FROM hr_person p
                      LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID
                      LEFT JOIN hr_position pos ON p.HR_POSITION_ID = pos.HR_POSITION_ID
                      WHERE p.HR_DEPARTMENT_ID = :group_id 
                      AND (p.HR_DEPARTMENT_SUB_ID IS NULL OR p.HR_DEPARTMENT_SUB_ID = '' OR p.HR_DEPARTMENT_SUB_ID = '0')
                      AND p.ID != :group_head_id
                      AND p.HR_STATUS_ID = '01'
                      ORDER BY p.HR_FNAME ASC";
        $stmt_g_staff = $pdo3->prepare($sql_group_staff);
        $stmt_g_staff->execute([
            'group_id' => $group['HR_DEPARTMENT_ID'],
            'group_head_id' => $group['LEADER_HR_ID'] ?? 0
        ]);
        $group_staff_list = $stmt_g_staff->fetchAll(PDO::FETCH_ASSOC);
        foreach ($group_staff_list as &$staff) {
            $staff['image'] = $staff['HR_IMAGE'] ? 'data:image/jpeg;base64,' . base64_encode($staff['HR_IMAGE']) : null;
            unset($staff['HR_IMAGE']);
        }

        $groups[] = [
            'id' => $group['HR_DEPARTMENT_ID'],
            'name' => $group['HR_DEPARTMENT_NAME'],
            'head' => $group_head,
            'subs' => $subs,
            'staff' => $group_staff_list
        ];
    }

    echo json_encode([
        "status" => "success",
        "data" => [
            "head" => $head,
            "groups" => $groups
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
