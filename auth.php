<?php
// Prevent any HTML output and handle errors properly for JSON API
error_reporting(E_ALL); // Keep error reporting for logging
ini_set('display_errors', 0); // Don't display errors to prevent HTML in JSON
ini_set('log_errors', 1); // Log errors instead

// Start output buffering to catch any unwanted output
ob_start();

// Set JSON response header early
header('Content-Type: application/json');

// Check if config.php exists
if (!file_exists('config.php')) {
    ob_clean(); // Clear any output
    echo json_encode([
        'success' => false,
        'message' => 'Configuration file not found.'
    ]);
    exit;
}

require_once 'config.php';

// Initialize database with sample data
function initializeDatabase() {
    try {
        $db = Database::getInstance()->getConnection();
    } catch (Exception $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return false;
    }

    try {
        // Check if we have sample data already
        $stmt = $db->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();

        if ($result['count'] == 0) {
            // Insert sample users
            $users = [
                [
                    'username' => 'admin',
                    'full_name' => 'System Administrator',
                    'email' => 'admin@chatapp.com',
                    'password' => hashPassword('admin123')
                ],
                [
                    'username' => 'john_doe',
                    'full_name' => 'John Doe',
                    'email' => 'john@example.com',
                    'password' => hashPassword('password123')
                ],
                [
                    'username' => 'jane_smith',
                    'full_name' => 'Jane Smith',
                    'email' => 'jane@example.com',
                    'password' => hashPassword('password123')
                ],
                [
                    'username' => 'mike_wilson',
                    'full_name' => 'Mike Wilson',
                    'email' => 'mike@example.com',
                    'password' => hashPassword('password123')
                ],
                [
                    'username' => 'sarah_jones',
                    'full_name' => 'Sarah Jones',
                    'email' => 'sarah@example.com',
                    'password' => hashPassword('password123')
                ]
            ];

            $stmt = $db->prepare("INSERT INTO users (username, full_name, email, password_hash) VALUES (?, ?, ?, ?)");
            foreach ($users as $user) {
                $stmt->execute([
                    $user['username'],
                    $user['full_name'],
                    $user['email'],
                    $user['password']
                ]);
            }

            // Insert chat rooms
            $rooms = [
                ['name' => 'General', 'description' => 'General discussion for everyone', 'is_private' => false, 'created_by' => 1],
                ['name' => 'Tech Talk', 'description' => 'Discuss technology and programming', 'is_private' => false, 'created_by' => 1],
                ['name' => 'Random', 'description' => 'Random conversations and fun', 'is_private' => false, 'created_by' => 1],
                ['name' => 'Private Group', 'description' => 'Private discussion group', 'is_private' => true, 'created_by' => 1]
            ];

            $stmt = $db->prepare("INSERT INTO chat_rooms (name, description, is_private, created_by) VALUES (?, ?, ?, ?)");
            foreach ($rooms as $room) {
                $stmt->execute([$room['name'], $room['description'], $room['is_private'], $room['created_by']]);
            }

            // Add users to rooms
            $participants = [
                [1, 1, 'admin'], [1, 2, 'member'], [1, 3, 'member'], [1, 4, 'member'], [1, 5, 'member'],
                [2, 1, 'admin'], [2, 2, 'member'], [2, 4, 'member'],
                [3, 1, 'admin'], [3, 3, 'member'], [3, 5, 'member'],
                [4, 1, 'admin'], [4, 2, 'member']
            ];

            $stmt = $db->prepare("INSERT INTO room_participants (room_id, user_id, role) VALUES (?, ?, ?)");
            foreach ($participants as $participant) {
                $stmt->execute($participant);
            }

            // Insert sample messages
            $messages = [
                [1, 1, 'Welcome to ChatApp! This is the general discussion room.', 'text'],
                [1, 2, 'Hello everyone! Great to be here.', 'text'],
                [1, 3, 'Hi! Looking forward to chatting with you all.', 'text'],
                [2, 1, 'Welcome to Tech Talk! Share your programming questions and insights here.', 'text'],
                [2, 4, 'Has anyone tried the new PHP 8.3 features?', 'text'],
                [3, 3, 'What is everyone up to this weekend?', 'text']
            ];

            $stmt = $db->prepare("INSERT INTO messages (room_id, user_id, message_text, message_type) VALUES (?, ?, ?, ?)");
            foreach ($messages as $message) {
                $stmt->execute($message);
            }

            error_log("Sample data inserted successfully");
            return true;
        }
        return true;
    } catch (PDOException $e) {
        error_log("Database initialization failed: " . $e->getMessage());
        return false;
    }
}

// Authenticate user
function authenticateUser($username, $password) {
    try {
        $db = Database::getInstance()->getConnection();
    } catch (Exception $e) {
        error_log("Database connection failed during auth: " . $e->getMessage());
        return false;
    }

    try {
        $stmt = $db->prepare("SELECT id, username, full_name, email, password_hash FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && function_exists('verifyPassword') && verifyPassword($password, $user['password_hash'])) {
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

// Check required functions exist
if (!function_exists('hashPassword') || !function_exists('verifyPassword')) {
    ob_clean(); // Clear any output
    echo json_encode([
        'success' => false,
        'message' => 'Required password functions not found in config.php'
    ]);
    exit;
}

// Initialize database (but don't run the full table creation from config.php)
if (!initializeDatabase()) {
    ob_clean(); // Clear any output
    echo json_encode([
        'success' => false,
        'message' => 'Database initialization failed'
    ]);
    exit;
}

// Handle action
$action = $_GET['action'] ?? ($_POST['action'] ?? 'login');

// Clear any unwanted output before sending JSON
ob_clean();

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
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action.'
        ]);
        break;
}
?>