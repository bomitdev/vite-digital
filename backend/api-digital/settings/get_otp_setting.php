<?php
require_once "../../config.php";

$settingsFile = __DIR__ . '/../../config_settings.json';

// Default settings
$settings = [
    'is_otp_enabled' => true
];

if (file_exists($settingsFile)) {
    $content = file_get_contents($settingsFile);
    $decoded = json_decode($content, true);
    if (is_array($decoded)) {
        $settings = array_merge($settings, $decoded);
    }
}

header('Content-Type: application/json');
echo json_encode(['status' => 'success', 'data' => $settings]);
