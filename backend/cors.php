<?php
// backend/cors.php

// Define allowed origins
$allowedOrigins = [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'http://localhost:3000',
    'http://localhost',
    // Add exact IP/Domain of your new machine if known, otherwise dynamic check below handles many cases
];

// Get the Origin header from the request
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Check if the origin is allowed (or just reflect it back if you want to be very permissive for this internal app)
// For strict security, check in_array($origin, $allowedOrigins). 
// For "lazy" internal app development where IP changes often:
if (!empty($origin)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    // Fallback for non-browser tools or same-origin
    header("Access-Control-Allow-Origin: *");
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
