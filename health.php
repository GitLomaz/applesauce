<?php
/**
 * Health Check Endpoint for Cloud Run
 * 
 * This endpoint is used by Cloud Run to check if the application is healthy.
 * It verifies:
 * 1. PHP is running
 * 2. Database connection is working
 * 3. Basic application functionality
 */

// Disable error display for health checks
ini_set('display_errors', '0');

// Set JSON header
header('Content-Type: application/json');

// Initialize response
$response = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'checks' => []
];

$httpCode = 200;

try {
    // Check 1: PHP is running (if we got here, it is)
    $response['checks']['php'] = [
        'status' => 'ok',
        'version' => PHP_VERSION
    ];
    
    // Check 2: Required extensions
    $requiredExtensions = ['mysqli', 'json'];
    $missingExtensions = [];
    
    foreach ($requiredExtensions as $ext) {
        if (!extension_loaded($ext)) {
            $missingExtensions[] = $ext;
        }
    }
    
    if (empty($missingExtensions)) {
        $response['checks']['extensions'] = ['status' => 'ok'];
    } else {
        $response['checks']['extensions'] = [
            'status' => 'error',
            'missing' => $missingExtensions
        ];
        $response['status'] = 'unhealthy';
        $httpCode = 503;
    }
    
    // Check 3: Database connection (optional - only if config is present)
    if (file_exists(__DIR__ . '/config.php')) {
        try {
            require_once(__DIR__ . '/config.php');
            
            $conn = get_db_connection();
            
            if ($conn && $conn->ping()) {
                $response['checks']['database'] = ['status' => 'ok'];
            } else {
                $response['checks']['database'] = [
                    'status' => 'error',
                    'message' => 'Cannot connect to database'
                ];
                $response['status'] = 'unhealthy';
                $httpCode = 503;
            }
        } catch (Exception $e) {
            $response['checks']['database'] = [
                'status' => 'error',
                'message' => 'Database connection failed'
            ];
            // Don't fail health check on DB issues during startup
            // Just log it
            error_log("Health check DB error: " . $e->getMessage());
        }
    } else {
        $response['checks']['database'] = [
            'status' => 'skipped',
            'message' => 'Config not found'
        ];
    }
    
    // Check 4: File permissions
    if (is_writable(__DIR__)) {
        $response['checks']['filesystem'] = ['status' => 'ok'];
    } else {
        $response['checks']['filesystem'] = [
            'status' => 'warning',
            'message' => 'Directory not writable'
        ];
    }
    
} catch (Exception $e) {
    $response['status'] = 'unhealthy';
    $response['error'] = 'Health check failed';
    $httpCode = 503;
    error_log("Health check error: " . $e->getMessage());
}

// Set HTTP response code
http_response_code($httpCode);

// Output JSON response
echo json_encode($response, JSON_PRETTY_PRINT);
exit;
