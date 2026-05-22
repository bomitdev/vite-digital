<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require __DIR__ . '/../../config.php';

try {
    // Determine filters
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $isAdmin = isset($_GET['is_admin']) && $_GET['is_admin'] === 'true';
    $requester = isset($_GET['requester']) ? $_GET['requester'] : '';

    // Build Query
    $sql = "SELECT * FROM computer_repair_requests WHERE 1=1";
    $params = [];

    // Filter by requester if not admin
    if (!$isAdmin) {
        if (!empty($requester)) {
            $sql .= " AND requester_name = ?";
            $params[] = $requester;
        } else {
            // Safety: Non-admin must provide requester name to see anything
            $sql .= " AND 1=0";
        }
    }

    if (!empty($status)) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }

    if (!empty($search)) {
        $sql .= " AND (ticket_no LIKE ? OR requester_name LIKE ? OR issue_title LIKE ? OR asset_code LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $pdo2->prepare($sql);
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $requests
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
