<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';
require_once __DIR__ . '/it_log_helper.php';

// Secure Auth
$userData = authGuard();

if (!isset($pdo2)) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

// Support both single request and multiple items approval
$approvals = [];

if (isset($data->approvals) && is_array($data->approvals)) {
    $approvals = $data->approvals;
} elseif (isset($data->id)) {
    $approvals[] = [
        'id' => $data->id,
        'approved_quantity' => $data->approved_quantity ?? null,
        'admin_note' => $data->admin_note ?? 'อนุมัติผ่านระบบ'
    ];
} elseif (isset($data->request_no)) {
    $stmtFind = $pdo2->prepare("SELECT id, quantity FROM mt_it_requests WHERE request_no = :rno AND status = 'pending'");
    $stmtFind->execute([':rno' => $data->request_no]);
    $items = $stmtFind->fetchAll(PDO::FETCH_ASSOC);
    foreach ($items as $it) {
        $approvals[] = [
            'id' => $it['id'],
            'approved_quantity' => $it['quantity'],
            'admin_note' => $data->admin_note ?? 'อนุมัติผ่านระบบ'
        ];
    }
}

if (empty($approvals)) {
    echo json_encode(['success' => false, 'message' => 'No requests specified to approve']);
    exit;
}

try {
    $pdo2->beginTransaction();

    $approvedGroups = [];

    foreach ($approvals as $approval) {
        $approval = (array) $approval;
        if (!isset($approval['id'])) {
            throw new Exception("Approval item missing 'id'");
        }
        $id = intval($approval['id']);

        $stmt = $pdo2->prepare("
            SELECT r.*, m.balance, m.name as material_name, m.unit as material_unit, m.id as material_id 
            FROM mt_it_requests r
            JOIN mt_it_materials m ON r.material_id = m.id
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
            $rejectNote = !empty($admin_note) && $admin_note !== 'Approved via system' && $admin_note !== 'อนุมัติผ่านระบบ' 
                ? $admin_note 
                : 'สินค้าในคลังหมด ไม่สามารถจ่ายได้';

            $stmtUpdate = $pdo2->prepare("
                UPDATE mt_it_requests 
                SET status = 'rejected', 
                    admin_note = :admin_note 
                WHERE id = :id
            ");
            $stmtUpdate->execute([
                ':admin_note' => $rejectNote,
                ':id' => $id
            ]);

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
            UPDATE mt_it_requests 
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
        $stmtLots = $pdo2->prepare("SELECT * FROM mt_it_lots WHERE material_id = :id AND remaining_qty > 0 ORDER BY receive_date ASC, id ASC FOR UPDATE");
        $stmtLots->execute([':id' => $request['material_id']]);
        $lots = $stmtLots->fetchAll();

        $remaining_to_fulfill = $approved_quantity;
        $total_out_price = 0;
        $lots_used = [];

        foreach ($lots as $lot) {
            if ($remaining_to_fulfill <= 0) break;

            $take_qty = min($lot['remaining_qty'], $remaining_to_fulfill);
            $total_out_price += ($take_qty * $lot['price_per_unit']);
            $remaining_to_fulfill -= $take_qty;
            
            $new_lot_qty = $lot['remaining_qty'] - $take_qty;

            $stmtUpdateLot = $pdo2->prepare("UPDATE mt_it_lots SET remaining_qty = :qty WHERE id = :id");
            $stmtUpdateLot->execute([':qty' => $new_lot_qty, ':id' => $lot['id']]);

            $lots_used[] = [
                'lot_id' => $lot['id'],
                'qty' => $take_qty,
                'price' => $lot['price_per_unit']
            ];
        }

        if ($remaining_to_fulfill > 0) {
            $stmtMatPrice = $pdo2->prepare("SELECT price_per_unit FROM mt_it_materials WHERE id = :id");
            $stmtMatPrice->execute([':id' => $request['material_id']]);
            $matRow = $stmtMatPrice->fetch();
            $unitPrice = $matRow ? floatval($matRow['price_per_unit']) : 0;

            $stmtNewLot = $pdo2->prepare("
                INSERT INTO mt_it_lots (material_id, lot_number, vendor, price_per_unit, original_qty, remaining_qty, receive_date)
                VALUES (:mid, 'LOT-AUTO-ADJUST', 'ระบบปรับยอดอัตโนมัติ', :price, :orig, 0, NOW())
            ");
            $stmtNewLot->execute([
                ':mid' => $request['material_id'],
                ':price' => $unitPrice,
                ':orig' => $remaining_to_fulfill
            ]);
            $autoLotId = $pdo2->lastInsertId();
            $total_out_price += ($remaining_to_fulfill * $unitPrice);
            $lots_used[] = [
                'lot_id' => $autoLotId,
                'qty' => $remaining_to_fulfill,
                'price' => $unitPrice
            ];
            $remaining_to_fulfill = 0;
        }

        // 4. Record transaction (Out)
        $stmtTx = $pdo2->prepare("
            INSERT INTO mt_it_transactions (material_id, action_type, quantity, total_price, action_date, user_profile_name, receiver_name, reference_dest, note)
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
        $txId = $pdo2->lastInsertId();

        // 4.5 Insert transaction lots
        if (!empty($lots_used)) {
            $stmtTxLot = $pdo2->prepare("INSERT INTO mt_it_transaction_lots (transaction_id, lot_id, quantity, price_per_unit) VALUES (?, ?, ?, ?)");
            foreach ($lots_used as $u) {
                $stmtTxLot->execute([$txId, $u['lot_id'], $u['qty'], $u['price']]);
            }
        }

        // 5. Deduct from Material balance
        $stmtStock = $pdo2->prepare("
            UPDATE mt_it_materials 
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

                $bubbleContents = [
                    "type" => "bubble",
                    "direction" => "ltr",
                    "header" => [
                        "type" => "box",
                        "layout" => "vertical",
                        "backgroundColor" => "#198754",
                        "contents" => [
                            [
                                "type" => "text",
                                "text" => "✅ อนุมัติจ่ายวัสดุคอมพิวเตอร์เรียบร้อย",
                                "weight" => "bold",
                                "size" => "md",
                                "color" => "#ffffff",
                                "align" => "center"
                            ]
                        ]
                    ],
                    "body" => [
                        "type" => "box",
                        "layout" => "vertical",
                        "spacing" => "sm",
                        "contents" => [
                            [
                                "type" => "text",
                                "text" => "เรียนคุณ: " . $recipientFullname,
                                "weight" => "bold",
                                "size" => "sm",
                                "color" => "#333333"
                            ],
                            [
                                "type" => "text",
                                "text" => "เลขที่คำขอ: " . $grp['request_no'],
                                "size" => "xs",
                                "color" => "#666666"
                            ],
                            [
                                "type" => "text",
                                "text" => "หน่วยงาน: " . $grp['department'],
                                "size" => "xs",
                                "color" => "#666666"
                            ],
                            [
                                "type" => "separator",
                                "margin" => "md"
                            ],
                            [
                                "type" => "text",
                                "text" => "📦 รายการที่ได้รับอนุมัติจ่าย (" . count($grp['items']) . " รายการ):",
                                "weight" => "bold",
                                "size" => "sm",
                                "color" => "#198754",
                                "margin" => "md"
                            ],
                            [
                                "type" => "box",
                                "layout" => "vertical",
                                "margin" => "sm",
                                "spacing" => "xs",
                                "contents" => $itemBoxes
                            ]
                        ]
                    ],
                    "footer" => [
                        "type" => "box",
                        "layout" => "vertical",
                        "contents" => [
                            [
                                "type" => "text",
                                "text" => "สามารถมารับพัสดุคอมพิวเตอร์ได้ที่ห้องศูนย์คอมพิวเตอร์",
                                "color" => "#888888",
                                "size" => "xs",
                                "align" => "center"
                            ]
                        ]
                    ]
                ];

                $linePayload = [
                    "messages" => [
                        [
                            "type" => "flex",
                            "altText" => "อนุมัติจ่ายวัสดุคอมพิวเตอร์แล้ว (เลขที่: " . $grp['request_no'] . ")",
                            "contents" => $bubbleContents
                        ]
                    ]
                ];

                $url = 'https://notify-api.moph.go.th/api/v1/notify';
                $headers = [
                    'Content-Type: application/json',
                    'client-key: ' . $mophClientKey,
                    'secret-key: ' . $mophSecretKey
                ];

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($linePayload, JSON_UNESCAPED_UNICODE));
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_exec($ch);
                unset($ch);
            }
        }
    } catch (Exception $notifyEx) {
        error_log("MOPH Notify Error in it_approve_request: " . $notifyEx->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'อนุมัติจ่ายวัสดุคอมพิวเตอร์และตัดสต๊อกเรียบร้อยแล้ว'
    ]);
} catch (Exception $e) {
    if ($pdo2->inTransaction()) {
        $pdo2->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
