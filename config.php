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
// PostgreSQL connection via Supabase: postgresql://postgres:[DB_PASS]@db.pasokuwgludhtolrxctu.supabase.co:5432/postgres
define('DB_HOST', getenv('DB_HOST') ?: 'db.pasokuwgludhtolrxctu.supabase.co');
define('DB_USER', getenv('DB_USER') ?: 'postgres');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'postgres');
define('DB_PORT', getenv('DB_PORT') ?: '5432');

// No Cloud SQL Unix socket: use TCP env vars for all environments

// Session configuration
define('SESSION_TIMEOUT_MINUTES', getenv('SESSION_TIMEOUT_MINUTES') ?: 15);

// Application environment
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', getenv('APP_DEBUG') === 'true');

/**
 * Get database connection
 * PostgreSQL connection using PDO
 * 
 * @return PDO Database connection object
 * @throws Exception if connection fails
 */
function get_db_connection() {
    static $conn = null;
    
    // Return existing connection if available
    if ($conn !== null) {
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

        // Build PostgreSQL DSN
        $dsn = sprintf(
            "pgsql:host=%s;port=%s;dbname=%s;sslmode=require",
            DB_HOST,
            DB_PORT,
            DB_NAME
        );
        
        // Create PDO connection with error mode set to exceptions
        $conn = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        error_log("PostgreSQL connection established successfully");
        
        return $conn;
        
    } catch (PDOException $e) {
        error_log("Fatal error connecting to database: " . $e->getMessage());
        throw new Exception("Database connection failed");
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
 * Result wrapper class to bridge PDO and mysqli-style code
 */
class PDOResultWrapper {
    private $statement;
    
    public function __construct($statement) {
        $this->statement = $statement;
    }
    
    public function fetch($mode = PDO::FETCH_ASSOC) {
        return $this->statement ? $this->statement->fetch($mode) : false;
    }
    
    public function rowCount() {
        return $this->statement ? $this->statement->rowCount() : 0;
    }
    
    public function __call($method, $args) {
        if ($this->statement && method_exists($this->statement, $method)) {
            return call_user_func_array([$this->statement, $method], $args);
        }
        return null;
    }
}

/**
 * Convert MySQL-style backticks to PostgreSQL-compatible syntax
 * 
 * @param string $query SQL query with MySQL backticks
 * @return string Query with backticks removed
 */
function convert_mysql_to_postgres($query) {
    // Remove backticks - PostgreSQL doesn't use them
    return str_replace('`', '', $query);
}

/**
 * Execute SQL query with error handling
 * 
 * @param string $query SQL query to execute
 * @param PDO $conn Database connection
 * @return PDOResultWrapper|bool Query result wrapper
 * @throws Exception if query fails
 */
function sql_query($query, $conn) {
    try {
        // Convert MySQL syntax to PostgreSQL
        $query = convert_mysql_to_postgres($query);
        $result = $conn->query($query);
        if ($result === false) {
            $error = "SQL Error: Query failed | Query: " . $query;
            error_log($error);
            if (APP_DEBUG) {
                throw new Exception($error);
            }
            return false;
        }
        return new PDOResultWrapper($result);
    } catch (PDOException $e) {
        // Log masked environment and connection info to help diagnose auth/permission errors
        $raw_env_pass = getenv('DB_PASS');
        $masked_pass = '';
        if ($raw_env_pass === false || $raw_env_pass === '') {
            $masked_pass = '(empty)';
        } else {
            $masked_pass = strlen($raw_env_pass) > 2 ? substr($raw_env_pass,0,1) . '***' . substr($raw_env_pass,-1) : '***';
        }
        error_log(sprintf('[DB QUERY ERROR] user=%s pass=%s host=%s db=%s code=%s err=%s query="%s"', DB_USER, $masked_pass, DB_HOST, DB_NAME, $e->getCode(), $e->getMessage(), str_replace("\n", ' ', substr($query,0,500))));
        if (APP_DEBUG) {
            throw $e;
        }
        return false;
    }
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

/**
 * Database compatibility functions for PDO
 * These provide mysqli-style function names that work with PDO
 * NOTE: This requires mysqli extension to NOT be loaded
 */

/**
 * Escape string for SQL queries
 * 
 * @param PDO $conn Database connection
 * @param string $string String to escape
 * @return string Escaped string
 */
function mysqli_real_escape_string($conn, $string) {
    if ($conn instanceof PDO) {
        // PDO::quote() adds quotes, so strip them
        $quoted = $conn->quote($string);
        return substr($quoted, 1, -1);
    }
    // Fallback if somehow mysqli object is passed
    return addslashes($string);
}

/**
 * Fetch array from query result
 * 
 * @param PDOResultWrapper $result Query result
 * @param int $mode Fetch mode (ignored, always returns ASSOC)
 * @return array|false Result row
 */
function mysqli_fetch_array($result, $mode = null) {
    if (!$result || !($result instanceof PDOResultWrapper)) {
        return false;
    }
    return $result->fetch(PDO::FETCH_ASSOC);
}

/**
 * Fetch associative array from query result
 * 
 * @param PDOResultWrapper $result Query result
 * @return array|false Result row
 */
function mysqli_fetch_assoc($result) {
    return mysqli_fetch_array($result);
}

/**
 * Get number of rows from query result
 * 
 * @param PDOResultWrapper $result Query result  
 * @return int Number of rows
 */
function mysqli_num_rows($result) {
    if (!$result || !($result instanceof PDOResultWrapper)) {
        return 0;
    }
    return $result->rowCount();
}

/**
 * Prepare a statement (PDO compatibility)
 * 
 * @param PDO $conn Database connection
 * @param string $query SQL query to prepare
 * @return PDOStatement|false Prepared statement
 */
function mysqli_prepare($conn, $query) {
    if ($conn instanceof PDO) {
        $query = convert_mysql_to_postgres($query);
        return $conn->prepare($query);
    }
    return false;
}

/**
 * Bind parameters to prepared statement (PDO compatibility)
 * 
 * @param PDOStatement $stmt Prepared statement
 * @param string $types Parameter types (ignored in PDO)
 * @param mixed ...$vars Variables to bind
 * @return bool Success
 */
function mysqli_stmt_bind_param($stmt, $types, &...$vars) {
    if (!$stmt) return false;
    try {
        $i = 1;
        foreach ($vars as &$var) {
            $stmt->bindParam($i++, $var);
        }
        return true;
    } catch (PDOException $e) {
        error_log("mysqli_stmt_bind_param error: " . $e->getMessage());
        return false;
    }
}

/**
 * Execute prepared statement (PDO compatibility)
 * 
 * @param PDOStatement $stmt Prepared statement
 * @return bool Success
 */
function mysqli_stmt_execute($stmt) {
    if (!$stmt) return false;
    try {
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("mysqli_stmt_execute error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get result from prepared statement (PDO compatibility)
 * 
 * @param PDOStatement $stmt Prepared statement
 * @return PDOResultWrapper Result wrapper
 */
function mysqli_stmt_get_result($stmt) {
    return new PDOResultWrapper($stmt);
}

/**
 * Close prepared statement (PDO compatibility)
 * 
 * @param PDOStatement $stmt Prepared statement
 * @return bool Success
 */
function mysqli_stmt_close($stmt) {
    // PDO statements are closed automatically
    return true;
}

// Define constants
if (!defined('MYSQLI_ASSOC')) {
    define('MYSQLI_ASSOC', 1);
}
