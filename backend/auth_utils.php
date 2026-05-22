<?php
// backend/auth_utils.php

// Define a secret key for HMAC signing. 
// In production, this should be a long, random string stored in .env
$secret_key = $_ENV['JWT_SECRET'] ?? 'default_secret_key_10985_cnm';

/**
 * Generate a signed token (Payload + HMAC Signature)
 */
function generateToken($payload)
{
    global $secret_key;
    $jsonPayload = json_encode($payload);
    $base64Payload = base64_encode($jsonPayload);

    // Create signature using HMAC-SHA256
    $signature = hash_hmac('sha256', $base64Payload, $secret_key);

    // Return combined token: payload.signature
    return $base64Payload . '.' . $signature;
}

/**
 * Verify a signed token
 */
function verifyToken($token)
{
    global $secret_key;
    $parts = explode('.', $token);

    if (count($parts) !== 2) {
        return false;
    }

    $payload = $parts[0];
    $signature = $parts[1];

    // Recalculate signature
    $validSignature = hash_hmac('sha256', $payload, $secret_key);

    if (hash_equals($validSignature, $signature)) {
        return json_decode(base64_decode($payload), true);
    }

    return false;
}

/**
 * Guard function to be called at the start of protected API files
 */
function authGuard()
{
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
        $userData = verifyToken($token);

        if ($userData) {
            // Check for expiration (optional, e.g., 24 hours)
            if (isset($userData['exp']) && time() > $userData['exp']) {
                http_response_code(401);
                echo json_encode(["success" => false, "message" => "Token expired"]);
                exit;
            }
            return $userData;
        }
    }

    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit;
}

/**
 * Optional guard: returns user data if valid token exists, otherwise returns null.
 * Does NOT exit the script on failure.
 */
function authOptional()
{
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
        return verifyToken($token);
    }

    return null;
}
