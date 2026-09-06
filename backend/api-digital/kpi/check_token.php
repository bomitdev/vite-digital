<?php
require_once __DIR__ . '/../../config.php';

function sendLineNotify(string $token, string $message) {
    $ch = curl_init();
    $url = "https://notify-api.line.me/api/notify";
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "message=" . urlencode($message));
    $headers = array('Content-type: application/x-www-form-urlencoded', 'Authorization: Bearer ' . $token);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    if ($result === false) {
        return curl_error($ch);
    }
    return json_decode((string)$result, true);
}

$token = "2UkOMkbQZTXSM3Xp5SVNnKnOFETyH1LyYMd6dHrhjpU";
$res = sendLineNotify($token, "Test from Antigravity");
echo json_encode($res);
