<?php
// CORS helper - allow specific origins with credentials
// Include this before any output on endpoints that need CORS

// Define allowed origins
$allowed_origins = [
    'http://localhost:8080',
    'https://gitlomaz.github.io'
];

// Get the origin of the request
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Check if origin is allowed
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    // Fallback to localhost for development
    header('Access-Control-Allow-Origin: http://localhost:8080');
}

// Enable credentials (required for cookies/sessions)
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
header('Access-Control-Max-Age: 3600');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// End of cors.php

