<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require __DIR__ . '/../../config.php';

try {
    $action = isset($_GET['action']) ? $_GET['action'] : 'getAll';
    $month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

    if ($action == 'getAll') {
        // Fetch all categories
        $stmtCtx = $pdo2->query("SELECT * FROM kpi_categories ORDER BY id");
        $categories = $stmtCtx->fetchAll();

    

        $year = isset($_GET['year']) ? intval($_GET['year']) : null;
        $subQuery = "SELECT MAX(period_date) FROM kpi_entries WHERE kpi_id = k.id";
        $yearFilter = "";

        if ($year) {
            $gYear = $year - 543;
            $prevYear = $gYear - 1;
            $startDate = "$prevYear-10-01";
            $endDate = "$gYear-09-30";
            $yearFilter = " AND period_date BETWEEN '$startDate' AND '$endDate'";
            $subQuery .= $yearFilter;
        }

        $sql = "SELECT 
                    k.id, k.code, k.name, k.description, k.target_value, k.target_operator, k.unit, k.category_id,
                    k.kpi_periodicity, k.numerator_label, k.denominator_label, k.calculation_type, k.multiplier, k.responsible_person, k.kpi_level,
                    e.actual_value, e.period_date, e.target_value_snapshot,
                    (SELECT GROUP_CONCAT(period_date) FROM kpi_entries WHERE kpi_id = k.id $yearFilter) as reported_periods,
                    (SELECT GROUP_CONCAT(CONCAT(period_date, '|', actual_value)) FROM kpi_entries WHERE kpi_id = k.id $yearFilter) as period_data,
                    (SELECT COUNT(*) FROM kpi_entries e2 WHERE e2.kpi_id = k.id $yearFilter AND 
                        (
                            (k.target_operator = '>=' AND CAST(e2.actual_value AS DECIMAL(10,2)) < CAST(e2.target_value_snapshot AS DECIMAL(10,2))) OR
                            (k.target_operator = '<=' AND CAST(e2.actual_value AS DECIMAL(10,2)) > CAST(e2.target_value_snapshot AS DECIMAL(10,2))) OR
                            (k.target_operator = '>' AND CAST(e2.actual_value AS DECIMAL(10,2)) <= CAST(e2.target_value_snapshot AS DECIMAL(10,2))) OR
                            (k.target_operator = '<' AND CAST(e2.actual_value AS DECIMAL(10,2)) >= CAST(e2.target_value_snapshot AS DECIMAL(10,2))) OR
                            (k.target_operator = '=' AND CAST(e2.actual_value AS DECIMAL(10,2)) != CAST(e2.target_value_snapshot AS DECIMAL(10,2)))
                        )
                    ) as failed_periods_count
                FROM kpi_definitions k
                LEFT JOIN kpi_entries e ON k.id = e.kpi_id 
                AND e.period_date = ($subQuery)
                ORDER BY k.category_id, k.id";

        $stmtKpi = $pdo2->query($sql);
        $kpis = $stmtKpi->fetchAll();

        // Organize by category
        $result = [];
        foreach ($categories as $cat) {
            $catKpis = array_filter($kpis, function ($k) use ($cat) {
                return $k['category_id'] == $cat['id'];
            });
            $cat['kpis'] = array_values($catKpis);
            $result[] = $cat;
        }

        echo json_encode(['status' => 'success', 'data' => $result]);
    } elseif ($action == 'getHistory') {
        // History for charts
        $kpiId = isset($_GET['kpi_id']) ? $_GET['kpi_id'] : null;
        if ($kpiId) {
            $stmt = $pdo2->prepare("SELECT period_date, actual_value, target_value_snapshot FROM kpi_entries WHERE kpi_id = ? ORDER BY period_date ASC LIMIT 12");
            $stmt->execute([$kpiId]);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        }
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
