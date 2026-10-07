<?php
function insertAdminLog($pdo, $user_profile_name, $action, $details) {
    try {
        $stmt = $pdo->prepare("INSERT INTO mt_admin_logs (user_profile_name, action, details) VALUES (?, ?, ?)");
        $stmt->execute([$user_profile_name, $action, $details]);
    } catch (Exception $e) {
        // fail silently for logs
    }
}
