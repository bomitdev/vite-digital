<?php
require __DIR__ . '/../../config.php';
require __DIR__ . '/../../auth_utils.php';

$userData = authGuard();

try {
    $prefix = isset($_GET['prefix']) ? $_GET['prefix'] : '';
    
    if (empty($prefix)) {
        echo json_encode(['status' => 'success', 'data' => '01']);
        exit;
    }

    $sql = "SELECT asset_code FROM assets WHERE asset_code LIKE :prefix ORDER BY asset_code DESC LIMIT 1";
    $stmt = $pdo2->prepare($sql);
    $stmt->execute([':prefix' => $prefix . '%']);
    $last_asset = $stmt->fetch(PDO::FETCH_ASSOC);

    $next_seq = 1;
    if ($last_asset) {
        $code = $last_asset['asset_code'];
        // code format: 7440-013-0001/6901
        $seq_str = substr($code, strlen($prefix));
        
        // Find the first numbers in the remaining string
        if (preg_match('/^\d+/', $seq_str, $matches)) {
            $next_seq = intval($matches[0]) + 1;
        }
    }

    echo json_encode(['status' => 'success', 'data' => str_pad($next_seq, 2, '0', STR_PAD_LEFT)]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
