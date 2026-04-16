<?php
/**
 * Application Configuration for Cloud Run
 * All environment-specific settings should be configured via environment variables
 */

// Set timezone - Cloud Run prefers UTC
date_default_timezone_set('UTC');

// Error reporting configuration
error_reporting(E_ALL);

// Logging Configuration for Cloud Run
// Cloud Run captures stdout/stderr automatically
ini_set('display_errors', '0'); // Don't display errors to clients
ini_set('log_errors', '1');
ini_set('error_log', 'php://stderr'); // Send errors to stderr for Cloud Run

// Database Configuration
// Use environment variables for sensitive credentials
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'kalrul');
define('DB_PORT', getenv('DB_PORT') ?: '3306');

// No Cloud SQL Unix socket: use TCP env vars for all environments

// Session configuration
define('SESSION_TIMEOUT_MINUTES', getenv('SESSION_TIMEOUT_MINUTES') ?: 15);

// Application environment
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', getenv('APP_DEBUG') === 'true');

/**
 * Get database connection
 * Supports both standard TCP and Cloud SQL Unix socket connections
 * 
 * @return mysqli Database connection object
 * @throws Exception if connection fails
 */
function get_db_connection() {
    static $conn = null;
    
    // Return existing connection if available
    if ($conn !== null && $conn->ping()) {
        return $conn;
    }
    
    try {
        // Debug: log DB env info (masked password) to help diagnose intermittent auth failures
        $raw_env_pass = getenv('DB_PASS');
        $masked_pass = '';
        if ($raw_env_pass === false || $raw_env_pass === '') {
            $masked_pass = '(empty)';
        } else {
            $masked_pass = strlen($raw_env_pass) > 2 ? substr($raw_env_pass,0,1) . '***' . substr($raw_env_pass,-1) : '***';
        }
        error_log(sprintf('[DB DEBUG] host=%s user=%s pass=%s db=%s port=%s env_pass_present=%s', DB_HOST, DB_USER, $masked_pass, DB_NAME, DB_PORT, ($raw_env_pass !== false && $raw_env_pass !== '') ? 'true' : 'false'));

        // Always use TCP connection via environment variables
        $conn = new mysqli(
            DB_HOST,
            DB_USER,
            DB_PASS,
            DB_NAME,
            DB_PORT
        );
        
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error . " (errno " . $conn->connect_errno . ")");
            throw new Exception("Database connection failed");
        }
        
        // Set charset to UTF-8
        $conn->set_charset("utf8mb4");
        
        // Disable ONLY_FULL_GROUP_BY for legacy queries
        $conn->query("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
        
        return $conn;
        
    } catch (Exception $e) {
        error_log("Fatal error connecting to database: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Legacy function name compatibility
 */
function sql_connect() {
    return get_db_connection();
}

/**
 * Legacy function name compatibility  
 */
function createConnection() {
    return get_db_connection();
}

/**
 * Execute SQL query with error handling
 * 
 * @param string $query SQL query to execute
 * @param mysqli $conn Database connection
 * @return mysqli_result|bool Query result
 * @throws Exception if query fails
 */
function sql_query($query, $conn) {
    $result = $conn->query($query);
    
    if (!$result) {
        $error = "SQL Error: " . $conn->error . " | Query: " . $query;
        error_log($error);
        
        if (APP_DEBUG) {
            throw new Exception($error);
        }
        
        return false;
    }
    
    return $result;
}

/**
 * Log application messages to Cloud Run logs
 * 
 * @param string $message Message to log
 * @param string $level Log level (INFO, WARNING, ERROR)
 */
function app_log($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $formatted = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    if (in_array($level, ['ERROR', 'CRITICAL'])) {
        error_log($formatted);
    } else {
        echo $formatted;
    }
}
