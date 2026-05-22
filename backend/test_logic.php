<?php
// backend/test_logic.php
$_POST = [];
// Mock php://input
$mockData = json_encode(["username" => "suriya", "password" => "wrong"]);
// We can't easily mock php://input for another file, so we'll just copy-paste the logic or use a wrapper.
// Alternatively, we use curl to the local server.

function testLogin($user, $pass)
{
    echo "Testing login for $user / $pass...\n";
    $ch = curl_init("http://localhost/vue-app/vite-digital/backend/login.php");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["username" => $user, "password" => $pass]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "HTTP Code: $httpCode\n";
    echo "Response: $response\n\n";
}

testLogin("suriya", "123");
testLogin("suriya", "wrong");
testLogin("nonexistent_user_123", "pass");
