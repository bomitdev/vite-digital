<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once __DIR__ . '/../../config.php';

try {
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    $month = isset($_GET['month']) ? intval($_GET['month']) : date('m');

    $startDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
    $endDate = date("Y-m-t", strtotime($startDate));

    $sql = "SELECT SEND_HR_NAME, GOTO_WITH, GO_TYPE_NAME, GO_NAME, DATE_END, DATE_BEGIN, RECORD_AT, BOOK_NUM, DETAIL_REPORT, DETAIL_EXPECT 
            FROM money_send_index 
            WHERE (DATE_BEGIN <= :endDate AND DATE_END >= :startDate)";
    
    $data = [];
    $pdos = [$pdo3, $pdo2, $pdo1];
    $success = false;
    $errorMessage = "";
    
    foreach($pdos as $pdo) {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['startDate' => $startDate, 'endDate' => $endDate]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $success = true;
            break;
        } catch (PDOException $ex) {
            $errorMessage = $ex->getMessage();
        }
    }
    
    if($success) {
        echo json_encode(["status" => "success", "data" => $data]);
    } else {
        echo json_encode(["status" => "error", "message" => $errorMessage]);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
