<?php
session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth_utils.php';

/** @var PDO $pdo3 */

// Secure Auth - ดึงข้อมูลผู้ใช้งานที่ล็อกอินอยู่
$userData = authGuard();
$requester_id = $userData['uid']; // ID ของผู้ที่กำลังใช้งานระบบ

try {
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

    // ID ของพนักงานที่ต้องการดึงข้อมูล (ถ้าไม่ส่งมา ให้ดูของตัวเอง)
    $target_user_id = (isset($_GET['user_id']) && !empty($_GET['user_id'])) ? $_GET['user_id'] : $requester_id;

    if (!$target_user_id) {
        echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
        exit();
    }

    // --- ส่วนการตรวจสอบสิทธิ์ (Security Check) ---
    // หากต้องการดูข้อมูลของ "คนอื่น" (Target != Requester)
    if ($requester_id != $target_user_id) {

        // 1. ดึงข้อมูลสิทธิ์ของผู้ใช้งาน (Requester)
        $stmtReq = $pdo3->prepare("SELECT HR_DEPARTMENT_SUB_ID, access_user FROM hr_person WHERE ID = :id");
        $stmtReq->execute([':id' => $requester_id]);
        $requester_info = $stmtReq->fetch(PDO::FETCH_ASSOC);

        $req_rights = isset($requester_info['access_user']) ? explode(':', $requester_info['access_user']) : [];
        $req_dept = $requester_info['HR_DEPARTMENT_SUB_ID'];

        $is_admin = (in_array('administrator', $req_rights) || in_array('Admin', $req_rights) || in_array('Super', $req_rights));
        $can_print_all = in_array('fingerscan_print_all', $req_rights);
        $can_user_all = in_array('fingerscan_user_all', $req_rights);

        if (!$is_admin) {
            // ดึงข้อมูลของพนักงานเป้าหมายมาเช็คเงื่อนไข
            $stmtTarget = $pdo3->prepare("SELECT HR_DEPARTMENT_SUB_ID, HR_PERSON_TYPE_ID, HR_POSITION_ID FROM hr_person WHERE ID = :id");
            $stmtTarget->execute([':id' => $target_user_id]);
            $target = $stmtTarget->fetch(PDO::FETCH_ASSOC);

            if ($target) {
                $allowed = false;

                // เงื่อนไข 1: fingerscan_print_all
                if ($can_print_all) {
                    if (
                        $target['HR_DEPARTMENT_SUB_ID'] == $req_dept ||
                        $target['HR_PERSON_TYPE_ID'] == '06' ||
                        $target['HR_POSITION_ID'] == '24'
                    ) {
                        $allowed = true;
                    }
                }

                // เงื่อนไข 2: fingerscan_user_all (เพิ่ม "พนักงานบริการ" ตรงนี้)
                if ($can_user_all && !$allowed) {
                    if (
                        $target['HR_DEPARTMENT_SUB_ID'] == $req_dept ||
                        in_array($target['HR_POSITION_ID'], ['66', '69', '71', '67'])
                    ) {
                        $allowed = true;
                    }
                }

                if (!$allowed) {
                    echo json_encode(['status' => 'error', 'message' => 'Unauthorized: คุณไม่มีสิทธิ์ดูข้อมูลพนักงานท่านนี้']);
                    exit();
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Target user not found']);
                exit();
            }
        }
    }

    $mode = $_GET['mode'] ?? 'view';

    if ($mode === 'export') {
        // --- EXPORT MODE: ดึงข้อมูลสรุปรายวัน ---
        $sql = "SELECT g.gov_date, f.mor, f.aft, f.ot, f.gstatus, f.status_name 
                FROM 10985_hos_government_date g 
                LEFT JOIN (
                    SELECT u.HR_FNAME as fname, u.HR_LNAME as lname, c.gdate, c.mor, c.aft, c.ot, c.gstatus, l.fstatus_name as status_name  
                    FROM 10985_hos_fingerscan_check c 
                    LEFT JOIN hr_person u ON c.fingerscan_user_id=u.FINGLE_ID 
                    LEFT JOIN 10985_hos_fingerscan_status l on c.gstatus=l.fstatus_id
                    WHERE u.ID = :user_id_1
                ) as f ON g.gov_date=f.gdate
                WHERE date(g.gov_date) BETWEEN :start_date_1 AND :end_date_1
                UNION
                SELECT c.gdate as gov_date, c.mor, c.aft, c.ot, c.gstatus, l.fstatus_name as status_name   
                FROM 10985_hos_fingerscan_check c 
                LEFT JOIN hr_person u ON c.fingerscan_user_id=u.FINGLE_ID 
                LEFT JOIN 10985_hos_fingerscan_status l on c.gstatus=l.fstatus_id
                WHERE u.ID = :user_id_2
                AND date(c.gdate) BETWEEN :start_date_2 AND :end_date_2
                AND c.gdate NOT IN(SELECT gov_date FROM 10985_hos_government_date WHERE date(gov_date) BETWEEN :start_date_3 AND :end_date_3)
                ORDER BY gov_date ASC";

        $stmt = $pdo3->prepare($sql);
        $stmt->bindParam(':user_id_1', $target_user_id);
        $stmt->bindParam(':start_date_1', $start_date);
        $stmt->bindParam(':end_date_1', $end_date);
        $stmt->bindParam(':user_id_2', $target_user_id);
        $stmt->bindParam(':start_date_2', $start_date);
        $stmt->bindParam(':end_date_2', $end_date);
        $stmt->bindParam(':start_date_3', $start_date);
        $stmt->bindParam(':end_date_3', $end_date);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $data = [];
        $sequence = 1;
        foreach ($result as $row) {
            $data[] = [
                'sequence'    => $sequence++,
                'gov_date'    => (new DateTime($row['gov_date']))->format('Y-m-d'),
                'mor'         => !empty($row['mor']) ? date('H:i', strtotime($row['mor'])) : '',
                'aft'         => !empty($row['aft']) ? date('H:i', strtotime($row['aft'])) : '',
                'ot'          => !empty($row['ot'])  ? date('H:i', strtotime($row['ot']))  : '',
                'status_name' => $row['status_name']
            ];
        }
    } else {
        // --- VIEW MODE: ดึงรายการสแกนนิ้วดิบ ---
        $sql = "SELECT DISTINCT
                    f.fingerscan_id,
                    CONCAT(hf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) AS hr_person_name,
                    f.fingerscan_datetime,
                    f.fingerscan_inout
                FROM `10985_hos_fingerscan` f
                LEFT JOIN hr_person p on f.fingerscan_user_id = p.FINGLE_ID
                LEFT JOIN hr_prefix hf ON p.HR_PREFIX_ID = hf.HR_PREFIX_ID
                WHERE p.ID = :user_id
                AND DATE(f.fingerscan_datetime) BETWEEN :start_date AND :end_date
                ORDER BY f.fingerscan_datetime ASC";

        $stmt = $pdo3->prepare($sql);
        $stmt->bindParam(':user_id', $target_user_id);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $data = [];
        $sequence = 1;
        foreach ($result as $row) {
            $dt = new DateTime($row['fingerscan_datetime']);
            $type_raw = $row['fingerscan_inout'];
            $type_display = ($type_raw === 'C/In') ? "เข้า" : (($type_raw === 'C/Out') ? "ออก" : $type_raw);

            $shift_display = "";
            $hour = (int)$dt->format('H');
            if ($type_display === 'เข้า') {
                if ($hour >= 5 && $hour <= 11) $shift_display = "เช้า";
                elseif ($hour >= 13 && $hour <= 17) $shift_display = "บ่าย";
                elseif ($hour >= 20 || $hour <= 4) $shift_display = "ดึก";
            }

            $data[] = [
                'sequence' => $sequence++,
                'id' => $row['fingerscan_id'],
                'date' => $dt->format('Y-m-d'),
                'time' => $dt->format('H:i'),
                'type' => $type_raw,
                'type_display' => $type_display,
                'shift' => $shift_display,
                'raw_datetime' => $row['fingerscan_datetime']
            ];
        }
    }

    echo json_encode(['status' => 'success', 'data' => $data]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
