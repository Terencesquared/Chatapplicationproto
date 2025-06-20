<?php
/**
 * Database Configuration File
 * Contains database connection settings and security configurations
 */

// Only display errors when explicitly requested, not in API responses
if (php_sapi_name() === 'cli' || (isset($_GET['debug']) && $_GET['debug'] === '1')) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    // For web requests, log errors but don't display them to prevent HTML in JSON
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
}

// Environment-based Database Configuration
if (isset($_SERVER['DATABASE_URL'])) {
    // Production - Render PostgreSQL
    $db_url = parse_url($_SERVER['DATABASE_URL']);
    define('DB_HOST', $db_url['host']);
    define('DB_NAME', ltrim($db_url['path'], '/'));
    define('DB_USER', $db_url['user']);
    define('DB_PASS', $db_url['pass']);
    define('DB_PORT', isset($db_url['port']) ? $db_url['port'] : 5432);
    define('DB_CHARSET', 'utf8');
    define('DB_TYPE', 'pgsql'); // PostgreSQL for production
} else {
    // Local development - MySQL
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'chat_app');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_PORT', 3306);
    define('DB_CHARSET', 'utf8mb4');
    define('DB_TYPE', 'mysql'); // MySQL for local
}

// Security Configuration
define('SECRET_KEY', 'your-secret-key-here-change-in-production');
define('SESSION_LIFETIME', 3600 * 24); // 24 hours
define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour

// Application Configuration
define('APP_NAME', 'ChatApp');
define('BASE_URL', 'http://localhost/chatapp');
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Database Connection Class
class Database {
    private static $instance = null;
    private $connection;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        try {
            // Build DSN based on database type
            if (DB_TYPE === 'pgsql') {
                // PostgreSQL DSN
                $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
            } else {
                // MySQL DSN
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            }
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            // Only add MySQL-specific attribute if using MySQL and the constant exists
            if (DB_TYPE === 'mysql' && defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES " . DB_CHARSET;
            }
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Alternative way to set charset for MySQL if the constant doesn't exist
            if (DB_TYPE === 'mysql' && !defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                $this->connection->exec("SET NAMES " . DB_CHARSET);
            }
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            // Don't expose sensitive database information to users
            if (php_sapi_name() === 'cli') {
                // If running from command line, show detailed error
                die("Database connection failed: " . $e->getMessage() . "\n");
            } else {
                // If running from web, throw exception to be handled by calling code
                throw new Exception("Database connection failed. Please check your configuration.");
            }
        }
    }
    
    /**
     * Get database instance (Singleton pattern)
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get database connection
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization of the instance
     */
    public function __wakeup() {}
}

/**
 * Initialize database tables (only call explicitly, not automatically)
 * Creates all required tables for the chat application
 */
function createDatabaseTables() {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Enable foreign key checks for MySQL
        if (DB_TYPE === 'mysql') {
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
        }
        
        if (php_sapi_name() === 'cli') {
            echo "Creating users table...\n";
        }
        
        if (DB_TYPE === 'pgsql') {
            // PostgreSQL version
            $db->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id SERIAL PRIMARY KEY,
                    username VARCHAR(50) NOT NULL UNIQUE,
                    full_name VARCHAR(100),
                    email VARCHAR(100) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    avatar_url VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
        } else {
            // MySQL version
            $db->exec("
                CREATE TABLE IF NOT EXISTS `users` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `username` varchar(50) NOT NULL,
                    `full_name` varchar(100) DEFAULT NULL,
                    `email` varchar(100) NOT NULL,
                    `password_hash` varchar(255) NOT NULL,
                    `avatar_url` varchar(255) DEFAULT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `username` (`username`),
                    UNIQUE KEY `email` (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ");
        }
        
        if (php_sapi_name() === 'cli') {
            echo "Creating chat_rooms table...\n";
        }
        
        if (DB_TYPE === 'pgsql') {
            // PostgreSQL version
            $db->exec("
                CREATE TABLE IF NOT EXISTS chat_rooms (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(100) NOT NULL UNIQUE,
                    description TEXT,
                    is_private BOOLEAN DEFAULT FALSE,
                    created_by INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
        } else {
            // MySQL version
            $db->exec("
                CREATE TABLE IF NOT EXISTS `chat_rooms` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(100) NOT NULL,
                    `description` text DEFAULT NULL,
                    `is_private` tinyint(1) DEFAULT 0,
                    `created_by` int(11) NOT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `name` (`name`),
                    KEY `created_by` (`created_by`),
                    CONSTRAINT `chat_rooms_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ");
        }
        
        if (php_sapi_name() === 'cli') {
            echo "Creating messages table...\n";
        }
        
        if (DB_TYPE === 'pgsql') {
            // PostgreSQL version - check if enum exists first
            $enumExists = $db->query("SELECT 1 FROM pg_type WHERE typname = 'message_type_enum'")->fetch();
            if (!$enumExists) {
                $db->exec("CREATE TYPE message_type_enum AS ENUM ('text', 'image', 'file')");
            }
            $db->exec("
                CREATE TABLE IF NOT EXISTS messages (
                    id SERIAL PRIMARY KEY,
                    room_id INTEGER NOT NULL REFERENCES chat_rooms(id) ON DELETE CASCADE,
                    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                    message_text TEXT NOT NULL,
                    message_type message_type_enum DEFAULT 'text',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
        } else {
            // MySQL version
            $db->exec("
                CREATE TABLE IF NOT EXISTS `messages` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `room_id` int(11) NOT NULL,
                    `user_id` int(11) NOT NULL,
                    `message_text` text NOT NULL,
                    `message_type` enum('text','image','file') DEFAULT 'text',
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    KEY `room_id` (`room_id`),
                    KEY `user_id` (`user_id`),
                    CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `chat_rooms` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ");
        }
        
        if (php_sapi_name() === 'cli') {
            echo "Creating room_participants table...\n";
        }
        
        if (DB_TYPE === 'pgsql') {
            // PostgreSQL version
            $enumExists = $db->query("SELECT 1 FROM pg_type WHERE typname = 'role_enum'")->fetch();
            if (!$enumExists) {
                $db->exec("CREATE TYPE role_enum AS ENUM ('admin', 'member')");
            }
            $db->exec("
                CREATE TABLE IF NOT EXISTS room_participants (
                    room_id INTEGER NOT NULL REFERENCES chat_rooms(id) ON DELETE CASCADE,
                    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                    role role_enum DEFAULT 'member',
                    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (room_id, user_id)
                )
            ");
        } else {
            // MySQL version
            $db->exec("
                CREATE TABLE IF NOT EXISTS `room_participants` (
                    `room_id` int(11) NOT NULL,
                    `user_id` int(11) NOT NULL,
                    `role` enum('admin','member') DEFAULT 'member',
                    `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`room_id`,`user_id`),
                    KEY `user_id` (`user_id`),
                    CONSTRAINT `room_participants_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `chat_rooms` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `room_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ");
        }
        
        if (php_sapi_name() === 'cli') {
            echo "All tables created successfully!\n";
        }
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Database table creation failed: " . $e->getMessage());
        throw new Exception("Database table creation failed: " . $e->getMessage());
    }
}

/**
 * Security helper functions
 */

/**
 * Generate CSRF token
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_LIFETIME) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && 
           isset($_SESSION['csrf_token_time']) &&
           (time() - $_SESSION['csrf_token_time']) <= CSRF_TOKEN_LIFETIME &&
           hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input data
 * @param mixed $data
 * @return mixed
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate secure session token
 * @return string
 */
function generateSessionToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Hash password securely
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_ARGON2ID);
}

/**
 * Verify password
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Only create tables if explicitly requested (to avoid output during API calls)
if (php_sapi_name() === 'cli' || (isset($_GET['init_db']) && $_GET['init_db'] === '1')) {
    createDatabaseTables();
}
?>