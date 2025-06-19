<?php
session_start(); // <-- IMPORTANT: Start session at the very top
require_once 'config.php';

// Set JSON response header early
header('Content-Type: application/json');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Initialize database with sample data

// Authenticate user
function authenticateUser($username, $password) {
    $db = Database::getInstance()->getConnection();

    try {
        $stmt = $db->prepare("SELECT id, username, full_name, email, password_hash FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && verifyPassword($password, $user['password_hash'])) {
            unset($user['password_hash']);
            return $user;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Authentication error: " . $e->getMessage());
        return false;
    }
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['full_name'], $_SESSION['email']);
}

// Initialize database
try {
    initializeDatabase();
} catch (Exception $e) {
    error_log("Database initialization error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database initialization failed'
    ]);
    exit;
}

// Handle action
$action = $_GET['action'] ?? ($_POST['action'] ?? 'login');

switch ($action) {
    case 'check':
        if (isLoggedIn()) {
            echo json_encode([
                'success' => true,
                'logged_in' => true,
                'user' => [
                    'id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'full_name' => $_SESSION['full_name'],
                    'email' => $_SESSION['email']
                ]
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'logged_in' => false
            ]);
        }
        break;

    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Username and password are required.'
                ]);
                exit;
            }

            $user = authenticateUser($username, $password);

            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['login_time'] = time();

                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful!',
                    'user' => $user
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid username or password.'
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid request method.'
            ]);
        }
        break;

    case 'logout':
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/'); // Clear session cookie
        echo json_encode([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action.'
        ]);
        break;
}

// Ensure no additional output
exit;
?>