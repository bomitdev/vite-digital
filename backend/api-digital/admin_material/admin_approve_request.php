<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';

// Secure Auth
$userData = authGuard();


if (!isset($pdo2)) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id) && (!isset($data->approvals) || !is_array($data->approvals)) && !isset($data->request_no)) {
    echo json_encode(['success' => false, 'message' => 'Request ID, request_no, or approvals array is required']);
    exit;
}

try {
    $pdo2->beginTransaction();

    $approvals = [];
    if (isset($data->approvals) && is_array($data->approvals)) {
        $approvals = $data->approvals;
    } elseif (isset($data->request_no)) {
        // Fetch all pending requests for this request_no
        $stmt_reqs = $pdo2->prepare("SELECT id, quantity FROM mt_admin_requests WHERE request_no = ? AND status = 'pending'");
        $stmt_reqs->execute([$data->request_no]);
        $items = $stmt_reqs->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as $item) {
            $approvals[] = [
                'id' => $item['id'],
                'approved_quantity' => $item['quantity'],
                'admin_note' => $data->admin_note ?? 'Approved via system (Bulk)'
            ];
        }
    } else {
        // Fallback for single approval (legacy)
        $approvals[] = [
            'id' => $data->id,
            'approved_quantity' => $data->approved_quantity ?? null,
            'admin_note' => $data->admin_note ?? ''
        ];
    }

    $approvedGroups = [];

    foreach ($approvals as $approval) {
        $approval = (array) $approval; // Cast to array

        // Ensure 'id' is present and valid
        if (!isset($approval['id'])) {
            throw new Exception("Approval item missing 'id'");
        }
        $id = intval($approval['id']);

        // Fetch request details and material balance in one query
        $stmt = $pdo2->prepare("
            SELECT r.*, m.balance, m.name as material_name, m.unit as material_unit, m.id as material_id 
            FROM mt_admin_requests r
            JOIN mt_admin_materials m ON r.material_id = m.id
            WHERE r.id = :id FOR UPDATE
        ");
        $stmt->execute([':id' => $id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            throw new Exception("Request ID $id not found");
        }

        if ($request['status'] !== 'pending') {
            throw new Exception("Request ID $id is already processed (" . $request['status'] . ")");
        }

        $originalQty = (int)$request['quantity'];
        $approved_quantity = isset($approval['approved_quantity']) ? (int)$approval['approved_quantity'] : $originalQty;
        $admin_note = $approval['admin_note'] ?? 'Approved via system';

        if ($approved_quantity <= 0) {
            // รายการนี้ไม่สามารถให้จ่ายได้เนื่องจากของในคลังหมด หรือไม่ต้องการจ่าย
            $rejectNote = !empty($admin_note) && $admin_note !== 'Approved via system' && $admin_note !== 'อนุมัติผ่านระบบ' 
                ? $admin_note 
                : 'สินค้าในคลังหมด ไม่สามารถจ่ายได้';

            $stmtUpdate = $pdo2->prepare("
                UPDATE mt_admin_requests 
                SET status = 'rejected', 
                    admin_note = :admin_note 
                WHERE id = :id
            ");
            $stmtUpdate->execute([
                ':admin_note' => $rejectNote,
                ':id' => $id
            ]);

            // บันทึกเพื่อส่งแจ้งเตือนว่ารายการนี้ไม่ได้จ่าย
            $reqNo = !empty($request['request_no']) ? $request['request_no'] : ('REQ-' . $id);
            if (!isset($approvedGroups[$reqNo])) {
                $approvedGroups[$reqNo] = [
                    'request_no' => $reqNo,
                    'requester_name' => $request['requester_name'],
                    'requester_id' => $request['requester_id'] ?? null,
                    'department' => $request['department'],
                    'admin_note' => $admin_note,
                    'items' => [],
                    'rejected_items' => []
                ];
            }
            if (!isset($approvedGroups[$reqNo]['rejected_items'])) {
                $approvedGroups[$reqNo]['rejected_items'] = [];
            }
            $approvedGroups[$reqNo]['rejected_items'][] = [
                'name' => $request['material_name'],
                'quantity' => $originalQty,
                'unit' => $request['material_unit'] ?? ''
            ];
            continue;
        }

        if ($approved_quantity > $originalQty) {
            throw new Exception("จำนวนจ่ายของ {$request['material_name']} เกินจำนวนที่ขอเบิก (ขอเบิก $originalQty, สั่งจ่าย $approved_quantity)");
        }

        if ($approved_quantity > $request['balance']) {
            throw new Exception("ไม่สามารถจ่าย {$request['material_name']} ได้ เนื่องจากของในคลังไม่พอ (คงเหลือ {$request['balance']} " . ($request['material_unit'] ?? '') . ", สั่งจ่าย $approved_quantity)");
        }

        // 2. Update Request status and approved quantity
        $stmtUpdate = $pdo2->prepare("
            UPDATE mt_admin_requests 
            SET status = 'approved', 
                quantity = :approved_quantity, 
                admin_note = :admin_note 
            WHERE id = :id
        ");
        $stmtUpdate->execute([
            ':approved_quantity' => $approved_quantity,
            ':admin_note' => $admin_note,
            ':id' => $id
        ]);

        // 3. Deduct from Lots (FIFO) and calculate total_price
        $stmtLots = $pdo2->prepare("SELECT * FROM mt_admin_lots WHERE material_id = :id AND remaining_qty > 0 ORDER BY receive_date ASC, id ASC FOR UPDATE");
        $stmtLots->execute([':id' => $request['material_id']]);
        $lots = $stmtLots->fetchAll();

        $remaining_to_fulfill = $approved_quantity;
        $total_out_price = 0;

        foreach ($lots as $lot) {
            if ($remaining_to_fulfill <= 0) break;

            $take_qty = min($lot['remaining_qty'], $remaining_to_fulfill);
            $total_out_price += ($take_qty * $lot['price_per_unit']);
            $remaining_to_fulfill -= $take_qty;
            
            $new_lot_qty = $lot['remaining_qty'] - $take_qty;

            $stmtUpdateLot = $pdo2->prepare("UPDATE mt_admin_lots SET remaining_qty = :qty WHERE id = :id");
            $stmtUpdateLot->execute([':qty' => $new_lot_qty, ':id' => $lot['id']]);
        }

        if ($remaining_to_fulfill > 0) {
            throw new Exception("สต็อก Lot ไม่พอจ่าย กรุณาตรวจสอบข้อมูลตั้งต้น (Material ID: " . $request['material_id'] . ")");
        }

        // 4. Record transaction (Out)
        $stmtTx = $pdo2->prepare("
            INSERT INTO mt_admin_transactions (material_id, action_type, quantity, total_price, action_date, user_profile_name, receiver_name, reference_dest, note)
            VALUES (:material_id, 'OUT', :quantity, :total_price, NOW(), :user, :receiver, :dest, :note)
        ");
        $stmtTx->execute([
            ':material_id' => $request['material_id'],
            ':quantity' => $approved_quantity,
            ':total_price' => $total_out_price,
            ':user' => $userData['name'] ?? $userData['user_profile_name'] ?? $userData['user'] ?? 'System Admin',
            ':receiver' => $request['requester_name'],
            ':dest' => $request['department'],
            ':note' => 'Approved request ID ' . $id . ($admin_note ? ' - ' . $admin_note : '')
        ]);

        // 5. Deduct from Material balance
        $stmtStock = $pdo2->prepare("
            UPDATE mt_admin_materials 
            SET balance = balance - :qty 
            WHERE id = :mid
        ");
        $stmtStock->execute([
            ':qty' => $approved_quantity,
            ':mid' => $request['material_id']
        ]);

        // Collect for Notification
        $reqNo = !empty($request['request_no']) ? $request['request_no'] : ('REQ-' . $id);
        if (!isset($approvedGroups[$reqNo])) {
            $approvedGroups[$reqNo] = [
                'request_no' => $reqNo,
                'requester_name' => $request['requester_name'],
                'requester_id' => $request['requester_id'] ?? null,
                'department' => $request['department'],
                'admin_note' => $admin_note,
                'items' => []
            ];
        }
        $approvedGroups[$reqNo]['items'][] = [
            'name' => $request['material_name'],
            'quantity' => $approved_quantity,
            'unit' => $request['material_unit'] ?? ''
        ];
    }

    $pdo2->commit();

    // --- SEND MOPH NOTIFY TO REQUESTER(S) ---
    try {
        foreach ($approvedGroups as $grp) {
            $mophClientKey = '';
            $mophSecretKey = '';
            $recipientFullname = $grp['requester_name'];

            if (isset($pdo3)) {
                $person = null;
                // 1. Try by requester_id if present
                if (!empty($grp['requester_id'])) {
                    $stmtP = $pdo3->prepare("
                        SELECT p.ID, p.MOPH_CLIENT_KEY, p.MOPH_SECRET_KEY, 
                               CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as fullname 
                        FROM hr_person p 
                        LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID 
                        WHERE p.ID = :id
                        LIMIT 1
                    ");
                    $stmtP->execute([':id' => $grp['requester_id']]);
                    $person = $stmtP->fetch(PDO::FETCH_ASSOC);
                }

                // 2. Fallback to name search in hr_person
                if (!$person || empty($person['MOPH_CLIENT_KEY']) || empty($person['MOPH_SECRET_KEY'])) {
                    $cleanName = trim($grp['requester_name']);
                    $stmtName = $pdo3->prepare("
                        SELECT p.ID, p.MOPH_CLIENT_KEY, p.MOPH_SECRET_KEY, 
                               CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) as fullname 
                        FROM hr_person p 
                        LEFT JOIN hr_prefix pf ON p.HR_PREFIX_ID = pf.HR_PREFIX_ID 
                        WHERE CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME) = :name1
                           OR CONCAT(p.HR_FNAME, ' ', p.HR_LNAME) = :name2
                           OR REPLACE(CONCAT(pf.HR_PREFIX_NAME, p.HR_FNAME, ' ', p.HR_LNAME), ' ', '') = REPLACE(:name3, ' ', '')
                           OR :name4 LIKE CONCAT('%', p.HR_FNAME, '%', p.HR_LNAME, '%')
                        ORDER BY (p.MOPH_CLIENT_KEY IS NOT NULL AND p.MOPH_CLIENT_KEY != '') DESC
                        LIMIT 1
                    ");
                    $stmtName->execute([
                        ':name1' => $cleanName,
                        ':name2' => $cleanName,
                        ':name3' => $cleanName,
                        ':name4' => $cleanName
                    ]);
                    $personFound = $stmtName->fetch(PDO::FETCH_ASSOC);
                    if ($personFound) {
                        $person = $personFound;
                    }
                }

                if ($person) {
                    $mophClientKey = trim($person['MOPH_CLIENT_KEY'] ?? '');
                    $mophSecretKey = trim($person['MOPH_SECRET_KEY'] ?? '');
                    if (!empty($person['fullname'])) {
                        $recipientFullname = $person['fullname'];
                    }
                }
            }

            // Send notification if user has registered MOPH keys
            if (!empty($mophClientKey) && !empty($mophSecretKey)) {
                $itemBoxes = [];
                foreach ($grp['items'] as $item) {
                    $u = !empty($item['unit']) ? ' ' . $item['unit'] : '';
                    $itemBoxes[] = [
                        "type" => "box",
                        "layout" => "baseline",
                        "spacing" => "sm",
                        "contents" => [
                            ["type" => "text", "text" => "- " . $item['name'], "wrap" => true, "color" => "#555555", "size" => "sm", "flex" => 4],
                            ["type" => "text", "text" => (string)$item['quantity'] . $u, "color" => "#198754", "size" => "sm", "flex" => 2, "align" => "end", "weight" => "bold"]
                        ]
                    ];
                }

                if (!empty($grp['rejected_items'])) {
                    $itemBoxes[] = [
                        "type" => "separator",
                        "margin" => "md"
                    ];
                    $itemBoxes[] = [
                        "type" => "text",
                        "text" => "❌ ไม่สามารถจ่ายได้ (ของหมด " . count($grp['rejected_items']) . " รายการ)",
                        "weight" => "bold",
                        "size" => "sm",
                        "color" => "#dc3545",
                        "margin" => "md"
                    ];
                    foreach ($grp['rejected_items'] as $rItem) {
                        $itemBoxes[] = [
                            "type" => "box",
                            "layout" => "baseline",
                            "spacing" => "sm",
                            "contents" => [
                                ["type" => "text", "text" => "- " . $rItem['name'], "wrap" => true, "color" => "#888888", "size" => "sm", "flex" => 4],
                                ["type" => "text", "text" => "ของหมดในคลัง", "color" => "#dc3545", "size" => "xs", "flex" => 2, "align" => "end", "weight" => "bold"]
                            ]
                        ];
                    }
                }

                $approverName = $userData['name'] ?? $userData['user_profile_name'] ?? $userData['user'] ?? 'เจ้าหน้าที่งานบริหารทั่วไป';

                $bodyContents = [
                    [
                        "type" => "box",
                        "layout" => "vertical",
                        "margin" => "md",
                        "spacing" => "sm",
                        "contents" => [
                            [
                                "type" => "box",
                                "layout" => "baseline",
                                "spacing" => "sm",
                                "contents" => [
                                    ["type" => "text", "text" => "เรียน", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                    ["type" => "text", "text" => $recipientFullname, "wrap" => true, "color" => "#333333", "size" => "sm", "weight" => "bold", "flex" => 5]
                                ]
                            ],
                            [
                                "type" => "box",
                                "layout" => "baseline",
                                "spacing" => "sm",
                                "contents" => [
                                    ["type" => "text", "text" => "เลขที่คำขอ", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                    ["type" => "text", "text" => $grp['request_no'], "wrap" => true, "color" => "#0d6efd", "size" => "sm", "weight" => "bold", "flex" => 5]
                                ]
                            ],
                            [
                                "type" => "box",
                                "layout" => "baseline",
                                "spacing" => "sm",
                                "contents" => [
                                    ["type" => "text", "text" => "หน่วยงาน", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                    ["type" => "text", "text" => $grp['department'], "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 5]
                                ]
                            ],
                            [
                                "type" => "box",
                                "layout" => "baseline",
                                "spacing" => "sm",
                                "contents" => [
                                    ["type" => "text", "text" => "สถานะ", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                    ["type" => "text", "text" => "อนุมัติจ่ายวัสดุแล้ว", "wrap" => true, "color" => "#198754", "size" => "sm", "weight" => "bold", "flex" => 5]
                                ]
                            ],
                            [
                                "type" => "box",
                                "layout" => "baseline",
                                "spacing" => "sm",
                                "contents" => [
                                    ["type" => "text", "text" => "ผู้อนุมัติ", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                    ["type" => "text", "text" => $approverName, "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 5]
                                ]
                            ],
                            [
                                "type" => "box",
                                "layout" => "baseline",
                                "spacing" => "sm",
                                "contents" => [
                                    ["type" => "text", "text" => "วันที่อนุมัติ", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                                    ["type" => "text", "text" => date('d/m/Y H:i น.'), "wrap" => true, "color" => "#666666", "size" => "sm", "flex" => 5]
                                ]
                            ]
                        ]
                    ],
                    [
                        "type" => "separator",
                        "margin" => "md"
                    ],
                    [
                        "type" => "text",
                        "text" => "รายการที่อนุมัติจ่าย (" . count($grp['items']) . " รายการ)",
                        "weight" => "bold",
                        "size" => "sm",
                        "color" => "#198754",
                        "margin" => "md"
                    ]
                ];

                if (!empty($grp['admin_note']) && $grp['admin_note'] !== 'Approved via system') {
                    $bodyContents[0]['contents'][] = [
                        "type" => "box",
                        "layout" => "baseline",
                        "spacing" => "sm",
                        "contents" => [
                            ["type" => "text", "text" => "หมายเหตุ", "color" => "#aaaaaa", "size" => "sm", "flex" => 2],
                            ["type" => "text", "text" => $grp['admin_note'], "wrap" => true, "color" => "#e67e22", "size" => "sm", "flex" => 5]
                        ]
                    ];
                }

                $userPayload = [
                    "messages" => [
                        [
                            "type" => "flex",
                            "altText" => "คำขอเบิกวัสดุได้รับการอนุมัติแล้ว (เลขที่: " . $grp['request_no'] . ")",
                            "contents" => [
                                "type" => "bubble",
                                "direction" => "ltr",
                                "header" => [
                                    "type" => "box",
                                    "layout" => "vertical",
                                    "backgroundColor" => "#198754",
                                    "contents" => [
                                        [
                                            "type" => "text",
                                            "text" => "✅ อนุมัติคำขอเบิกวัสดุแล้ว",
                                            "weight" => "bold",
                                            "size" => "md",
                                            "color" => "#ffffff",
                                            "align" => "center"
                                        ],
                                        [
                                            "type" => "text",
                                            "text" => "งานบริหารทั่วไปและธุรการ",
                                            "size" => "xs",
                                            "color" => "#d1e7dd",
                                            "align" => "center",
                                            "margin" => "xs"
                                        ]
                                    ]
                                ],
                                "body" => [
                                    "type" => "box",
                                    "layout" => "vertical",
                                    "contents" => array_merge($bodyContents, $itemBoxes)
                                ],
                                "footer" => [
                                    "type" => "box",
                                    "layout" => "vertical",
                                    "contents" => [
                                        [
                                            "type" => "text",
                                            "text" => "🏢 กรุณาติดต่อรับวัสดุได้ที่ งานบริหารทั่วไป",
                                            "size" => "xs",
                                            "color" => "#888888",
                                            "align" => "center"
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];

                $url = "https://morpromt2f.moph.go.th/api/notify/send";
                $headers = [
                    "Content-Type: application/json",
                    "client-key: {$mophClientKey}",
                    "secret-key: {$mophSecretKey}"
                ];

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($userPayload, JSON_UNESCAPED_UNICODE));
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_exec($ch);
                unset($ch);
            }
        }
    } catch (Exception $notifyEx) {
        error_log("MOPH Notify Error in admin_approve_request: " . $notifyEx->getMessage());
    }
    // --- SEND MOPH NOTIFY END ---

    echo json_encode(['success' => true, 'message' => 'Requests approved successfully']);
} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
}
