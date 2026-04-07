<?php
// CORS helper - always allow all origins
// Include this before any output on endpoints that need CORS

// Always allow any origin
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');

// NOTE: Do not send Access-Control-Allow-Credentials: true with a wildcard origin.

// Handle preflight request and exit early
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // short-circuit preflight with no content
    http_response_code(204);
    exit;
}

// End of cors.php

