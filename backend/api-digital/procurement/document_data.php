<?php
header("Content-Type: application/json; charset=UTF-8");
require __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_utils.php';

$userData = authGuard();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        $bill_id = $_GET['bill_id'] ?? null;
        if (!$bill_id) throw new Exception("bill_id is required");

        $stmt = $pdo2->prepare("SELECT * FROM procurement_documents_data WHERE bill_id = ?");
        $stmt->execute([$bill_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            // Return empty defaults if not exists
            echo json_encode(['status' => 'success', 'data' => null]);
        } else {
            // Decode JSON for committee
            $data['committee'] = $data['committee'] ? json_decode($data['committee'], true) : [];
            echo json_encode(['status' => 'success', 'data' => $data]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} elseif ($method === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $bill_id = $input['bill_id'] ?? null;
        if (!$bill_id) throw new Exception("bill_id is required");

        $doc_date = $input['doc_date'] ?? null;
        $to_person = $input['to_person'] ?? 'ผู้ว่าราชการจังหวัดอำนาจเจริญ';
        $reason = $input['reason'] ?? null;
        $budget = $input['budget'] ?? 0.00;
        $delivery_days = $input['delivery_days'] ?? 15;
        $committee = isset($input['committee']) ? json_encode($input['committee'], JSON_UNESCAPED_UNICODE) : null;
        $vendor_address = $input['vendor_address'] ?? null;
        $vendor_tax_id = $input['vendor_tax_id'] ?? null;
        $vendor_tel = $input['vendor_tel'] ?? null;
        $buyer_name = $input['buyer_name'] ?? null;
        $buyer_position = $input['buyer_position'] ?? null;
        $officer_name = $input['officer_name'] ?? null;
        $officer_position = $input['officer_position'] ?? null;
        $chief_officer_name = $input['chief_officer_name'] ?? null;
        $chief_officer_position = $input['chief_officer_position'] ?? null;

        // Check if exists
        $stmtCheck = $pdo2->prepare("SELECT id FROM procurement_documents_data WHERE bill_id = ?");
        $stmtCheck->execute([$bill_id]);
        $exists = $stmtCheck->fetchColumn();

        if ($exists) {
            $sql = "UPDATE procurement_documents_data SET 
                doc_date = :doc_date,
                to_person = :to_person,
                reason = :reason,
                budget = :budget,
                delivery_days = :delivery_days,
                committee = :committee,
                vendor_address = :vendor_address,
                vendor_tax_id = :vendor_tax_id,
                vendor_tel = :vendor_tel,
                buyer_name = :buyer_name,
                buyer_position = :buyer_position,
                officer_name = :officer_name,
                officer_position = :officer_position,
                chief_officer_name = :chief_officer_name,
                chief_officer_position = :chief_officer_position
                WHERE bill_id = :bill_id";
        } else {
            $sql = "INSERT INTO procurement_documents_data (
                bill_id, doc_date, to_person, reason, budget, delivery_days, committee, vendor_address, vendor_tax_id, vendor_tel, buyer_name, buyer_position, officer_name, officer_position, chief_officer_name, chief_officer_position
            ) VALUES (
                :bill_id, :doc_date, :to_person, :reason, :budget, :delivery_days, :committee, :vendor_address, :vendor_tax_id, :vendor_tel, :buyer_name, :buyer_position, :officer_name, :officer_position, :chief_officer_name, :chief_officer_position
            )";
        }

        $stmt = $pdo2->prepare($sql);
        $stmt->execute([
            ':bill_id' => $bill_id,
            ':doc_date' => $doc_date,
            ':to_person' => $to_person,
            ':reason' => $reason,
            ':budget' => $budget,
            ':delivery_days' => $delivery_days,
            ':committee' => $committee,
            ':vendor_address' => $vendor_address,
            ':vendor_tax_id' => $vendor_tax_id,
            ':vendor_tel' => $vendor_tel,
            ':buyer_name' => $buyer_name,
            ':buyer_position' => $buyer_position,
            ':officer_name' => $officer_name,
            ':officer_position' => $officer_position,
            ':chief_officer_name' => $chief_officer_name,
            ':chief_officer_position' => $chief_officer_position
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Document data saved.']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>
