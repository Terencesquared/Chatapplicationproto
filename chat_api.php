<?php
ini_set('error_log', __DIR__ . '/chat_errors.log');
ini_set('log_errors', 1);
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log that the file started
error_log("=== CHAT API STARTED === Action: " . ($_GET['action'] ?? 'none') . " Query: " . ($_GET['query'] ?? 'none'));
// Prevent any output before headers
ob_start();

session_start();
require_once 'config.php';
require_once 'chat.php';

// Clean any output that might have been generated
ob_clean();

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Error handling function
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

// Success response function
function sendSuccess($data = []) {
    echo json_encode(['success' => true] + $data);
    exit();
}

function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        sendError('Authentication required', 401);
    }
    return $_SESSION['user_id'];
}

function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) return null;

    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, username, full_name, avatar_url FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("getCurrentUser error: " . $e->getMessage());
        return null;
    }
}

// Initialize chat object with error handling
try {
    $chat = new Chat();
} catch (Exception $e) {
    error_log("Chat initialization error: " . $e->getMessage());
    sendError('System initialization failed');
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Main API logic with comprehensive error handling
try {
    switch ($action) {
        case 'get_rooms':
            $userId = checkAuth();
            
            // Debug logging
            error_log("Getting rooms for user ID: " . $userId);
            
            try {
                // Get rooms directly from database instead of relying on Chat class
                $db = Database::getInstance()->getConnection();
                
                // Get all public rooms and private rooms the user is a member of
                $query = "SELECT DISTINCT r.*, rp.role,
                            (SELECT COUNT(*) FROM messages m WHERE m.room_id = r.id) as message_count
                          FROM chat_rooms r 
                          LEFT JOIN room_participants rp ON r.id = rp.room_id AND rp.user_id = :user_id
                          WHERE r.is_private = 0 OR rp.user_id IS NOT NULL
                          ORDER BY r.created_at DESC";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $userId);
                $stmt->execute();
                $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                error_log("Found " . count($rooms) . " rooms");
                
                $formatted = [];
                foreach ($rooms as $room) {
                    try {
                        // Get last message for this room
                        $msgQuery = "SELECT m.*, u.username, u.full_name 
                                   FROM messages m 
                                   JOIN users u ON m.user_id = u.id 
                                   WHERE m.room_id = :room_id 
                                   ORDER BY m.created_at DESC 
                                   LIMIT 1";
                        
                        $msgStmt = $db->prepare($msgQuery);
                        $msgStmt->bindParam(':room_id', $room['id']);
                        $msgStmt->execute();
                        $lastMsg = $msgStmt->fetch(PDO::FETCH_ASSOC);
                        
                        $formatted[] = [
                            'id' => (int)$room['id'],
                            'name' => $room['name'] ?? 'Unnamed Room',
                            'description' => $room['description'] ?? '',
                            'avatar' => strtoupper(($room['name'] ?? 'U')[0]),
                            'lastMessage' => $lastMsg ? $lastMsg['message_text'] : 'No messages yet',
                            'lastTime' => $lastMsg ? date('H:i', strtotime($lastMsg['created_at'])) : '',
                            'messageCount' => (int)($room['message_count'] ?? 0),
                            'isPrivate' => (bool)($room['is_private'] ?? false),
                            'role' => $room['role'] ?? 'member'
                        ];
                    } catch (Exception $e) {
                        error_log("Error formatting room " . $room['id'] . ": " . $e->getMessage());
                        // Skip this room but continue with others
                        continue;
                    }
                }
                
                sendSuccess(['rooms' => $formatted]);
                
            } catch (Exception $e) {
                error_log("Error getting user rooms: " . $e->getMessage());
                sendError('Failed to load rooms: ' . $e->getMessage());
            }
            break;

        case 'create_room':
            if ($method !== 'POST') sendError('Method not allowed', 405);
            
            $userId = checkAuth();
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                sendError('Invalid JSON input');
            }
            
            if (empty($input['name'])) {
                sendError('Room name is required');
            }
            
            try {
                $db = Database::getInstance()->getConnection();
                
                // Insert new room
                $query = "INSERT INTO chat_rooms (name, description, is_private, created_by, created_at) 
                         VALUES (:name, :description, :is_private, :created_by, NOW())";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':name', $input['name']);
                $description = $input['description'] ?? '';
                $stmt->bindParam(':description', $description);
                $isPrivate = $input['isPrivate'] ?? false;
                $stmt->bindParam(':is_private', $isPrivate, PDO::PARAM_BOOL);
                $stmt->bindParam(':created_by', $userId);
                
                if ($stmt->execute()) {
                    $roomId = $db->lastInsertId();
                    
                    // Add creator as admin to room_participants
                    $participantQuery = "INSERT INTO room_participants (room_id, user_id, role, joined_at) 
                                       VALUES (:room_id, :user_id, 'admin', NOW())";
                    
                    $participantStmt = $db->prepare($participantQuery);
                    $participantStmt->bindParam(':room_id', $roomId);
                    $participantStmt->bindParam(':user_id', $userId);
                    $participantStmt->execute();
                    
                    sendSuccess([
                        'message' => 'Room created successfully',
                        'room_id' => (int)$roomId
                    ]);
                } else {
                    sendError('Failed to create room');
                }
                
            } catch (Exception $e) {
                error_log("Create room error: " . $e->getMessage());
                sendError('Failed to create room: ' . $e->getMessage());
            }
            break;

        case 'get_messages':
            $userId = checkAuth();
            $roomId = intval($_GET['room_id'] ?? 0);
            
            if ($roomId <= 0) {
                sendError('Invalid room ID');
            }
            
            try {
                $db = Database::getInstance()->getConnection();
                
                // Check if user has access to this room
                $accessQuery = "SELECT 1 FROM chat_rooms r 
                              LEFT JOIN room_participants rp ON r.id = rp.room_id AND rp.user_id = :user_id
                              WHERE r.id = :room_id AND (r.is_private = 0 OR rp.user_id IS NOT NULL)";
                
                $accessStmt = $db->prepare($accessQuery);
                $accessStmt->bindParam(':user_id', $userId);
                $accessStmt->bindParam(':room_id', $roomId);
                $accessStmt->execute();
                
                if (!$accessStmt->fetch()) {
                    sendError('Access denied', 403);
                }

                // Get messages
                $query = "SELECT m.*, u.username, u.full_name 
                         FROM messages m 
                         JOIN users u ON m.user_id = u.id 
                         WHERE m.room_id = :room_id 
                         ORDER BY m.created_at DESC 
                         LIMIT :limit OFFSET :offset";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':room_id', $roomId);
                $limit = intval($_GET['limit'] ?? 50);
                $offset = intval($_GET['offset'] ?? 0);
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
                
                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $formatted = array_map(function($m) {
                    return [
                        'id' => (int)$m['id'],
                        'author' => $m['full_name'] ?: $m['username'],
                        'avatar' => strtoupper(($m['username'] ?? 'U')[0]),
                        'text' => $m['message_text'] ?? '',
                        'time' => date('H:i A', strtotime($m['created_at'])),
                        'timestamp' => $m['created_at'],
                        'userId' => (int)$m['user_id'],
                        'messageType' => $m['message_type'] ?? 'text',
                        'isEdited' => (bool)($m['is_edited'] ?? false)
                    ];
                }, $messages);

                sendSuccess(['messages' => array_reverse($formatted)]); // Reverse to show oldest first
                
            } catch (Exception $e) {
                error_log("Get messages error: " . $e->getMessage());
                sendError('Failed to load messages: ' . $e->getMessage());
            }
            break;

        // Add this case to your existing chat_api.php switch statement
        

case 'get_friends':
    $userId = checkAuth();
    try {
        $db = Database::getInstance()->getConnection();
        $query = "SELECT u.id, u.username, u.full_name, u.email, u.avatar_url
                  FROM users u
                  INNER JOIN friends f ON (
                      (f.user_id = :user_id1 AND f.friend_id = u.id)
                      OR
                      (f.friend_id = :user_id2 AND f.user_id = u.id)
                  )
                  WHERE f.status = 'accepted'";
        $stmt = $db->prepare($query);
        $stmt->bindValue(':user_id1', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id2', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formatted = array_map(function($f) {
            return [
                'id' => (int)$f['id'],
                'username' => $f['username'],
                'full_name' => $f['full_name'],
                'email' => $f['email'],
                'avatar_url' => $f['avatar_url']
            ];
        }, $friends);

        sendSuccess(['friends' => $formatted]);
    } catch (Exception $e) {
        error_log("Get friends error: " . $e->getMessage());
        sendError('Failed to load friends');
    }
    break;




case 'search_users':
    try {
        $userId = checkAuth();
        $query = trim($_GET['query'] ?? '');
        
        error_log("=== SEARCH USERS DEBUG START ===");
        error_log("User ID: " . $userId);
        error_log("Query: " . $query);

        if (empty($query)) {
            sendSuccess(['users' => []]);
            exit;
        }

        $db = Database::getInstance()->getConnection();

        // First, let's check if the friends table exists and get exclude IDs safely
        $excludeIds = [(int)$userId]; // Always exclude yourself
        
        try {
            // Check if friends table exists
            $checkTable = $db->query("SHOW TABLES LIKE 'friends'");
            if ($checkTable->rowCount() > 0) {
                error_log("Friends table exists, getting exclude IDs");
                
                // Get friend IDs with proper error handling
                $excludeSql = "SELECT friend_id AS id FROM friends WHERE user_id = ? 
                              UNION 
                              SELECT user_id AS id FROM friends WHERE friend_id = ?";
                
                $excludeStmt = $db->prepare($excludeSql);
                if ($excludeStmt) {
                    $result = $excludeStmt->execute([$userId, $userId]);
                    if ($result) {
                        $friendIds = $excludeStmt->fetchAll(PDO::FETCH_COLUMN);
                        error_log("Found friend IDs: " . json_encode($friendIds));
                        
                        // Add friend IDs to exclude list
                        foreach ($friendIds as $friendId) {
                            if (is_numeric($friendId) && $friendId > 0) {
                                $excludeIds[] = (int)$friendId;
                            }
                        }
                    } else {
                        error_log("Failed to execute exclude query: " . json_encode($excludeStmt->errorInfo()));
                    }
                } else {
                    error_log("Failed to prepare exclude query: " . json_encode($db->errorInfo()));
                }
            } else {
                error_log("Friends table does not exist, only excluding self");
            }
        } catch (Exception $e) {
            error_log("Error getting exclude IDs: " . $e->getMessage());
            // Continue with just excluding self
        }
        
        // Remove duplicates
        $excludeIds = array_unique($excludeIds);
        error_log("Final exclude IDs: " . json_encode($excludeIds));

        // Build main search query
        $sql = "SELECT u.id, u.username, u.full_name, u.email, u.avatar_url 
                FROM users u 
                WHERE (u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
        
        $params = ['%' . $query . '%', '%' . $query . '%', '%' . $query . '%'];
        
        // Add exclude conditions
        if (!empty($excludeIds)) {
            $placeholders = str_repeat('?,', count($excludeIds) - 1) . '?';
            $sql .= " AND u.id NOT IN ($placeholders)";
            $params = array_merge($params, $excludeIds);
        }
        
        $sql .= " LIMIT 10";
        
        error_log("Final SQL: " . $sql);
        error_log("Final params: " . json_encode($params));
        error_log("Param count: " . count($params));

        $stmt = $db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare main query: " . json_encode($db->errorInfo()));
        }
        
        $result = $stmt->execute($params);
        if (!$result) {
            throw new Exception("Failed to execute main query: " . json_encode($stmt->errorInfo()));
        }
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Found users: " . count($users));

        $formatted = array_map(function($u) {
            return [
                'id' => (int)$u['id'],
                'username' => $u['username'] ?? '',
                'full_name' => $u['full_name'] ?? '',
                'email' => $u['email'] ?? '',
                'avatar_url' => $u['avatar_url'] ?? ''
            ];
        }, $users);

        error_log("=== SEARCH USERS DEBUG END ===");
        sendSuccess(['users' => $formatted]);
        
    } catch (Exception $e) {
        error_log("Search users error: " . $e->getMessage());
        error_log("Exception trace: " . $e->getTraceAsString());
        sendError('Failed to search users: ' . $e->getMessage());
    }
    break;
    
case 'add_friend':
    try {
        $userId = checkAuth();
        $data = json_decode(file_get_contents('php://input'), true);
        $friendId = intval($data['user_id'] ?? 0);
        
        error_log("=== ADD FRIEND DEBUG ===");
        error_log("Current user ID: " . $userId);
        error_log("Friend ID to add: " . $friendId);
        error_log("Raw input data: " . json_encode($data));

        if ($friendId <= 0 || $friendId == $userId) {
            error_log("Invalid friend ID or trying to add self");
            sendError('Invalid user');
            break;
        }

        $db = Database::getInstance()->getConnection();
        
        // Check if already friends or pending
        $checkSql = "SELECT * FROM friends WHERE 
                     (user_id = ? AND friend_id = ?) OR 
                     (user_id = ? AND friend_id = ?)";
        
        error_log("Check SQL: " . $checkSql);
        error_log("Check params: " . json_encode([$userId, $friendId, $friendId, $userId]));
        
        $stmt = $db->prepare($checkSql);
        if (!$stmt) {
            throw new Exception("Failed to prepare check query: " . json_encode($db->errorInfo()));
        }
        
        $result = $stmt->execute([$userId, $friendId, $friendId, $userId]);
        if (!$result) {
            throw new Exception("Failed to execute check query: " . json_encode($stmt->errorInfo()));
        }
        
        if ($stmt->fetch()) {
            error_log("Already friends or request pending");
            sendError('Already friends or request pending');
            break;
        }

        // Insert friend request as accepted
        $insertSql = "INSERT INTO friends (user_id, friend_id, status, requested_at, accepted_at) 
                      VALUES (?, ?, 'accepted', NOW(), NOW())";
        
        error_log("Insert SQL: " . $insertSql);
        error_log("Insert params: " . json_encode([$userId, $friendId]));
        
        $stmt = $db->prepare($insertSql);
        if (!$stmt) {
            throw new Exception("Failed to prepare insert query: " . json_encode($db->errorInfo()));
        }
        
        $result = $stmt->execute([$userId, $friendId]);
        if (!$result) {
            throw new Exception("Failed to execute insert query: " . json_encode($stmt->errorInfo()));
        }
        
        error_log("Friend added successfully");
        sendSuccess(['message' => 'Friend added']);
        
    } catch (Exception $e) {
        error_log("Add friend error: " . $e->getMessage());
        error_log("Exception trace: " . $e->getTraceAsString());
        sendError('Failed to add friend: ' . $e->getMessage());
    }
    break;

case 'get_account':
    try {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        // Use the same DB connection as the rest of your API
        $db = Database::getInstance()->getConnection();

        $query = "SELECT id, username, full_name, email, avatar_url, created_at FROM users WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'avatar_url' => $user['avatar_url'],
                'created_at' => $user['created_at']
            ]
        ]);
    } catch (PDOException $e) {
        error_log("Database error in get_account: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    } catch (Exception $e) {
        error_log("General error in get_account: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while fetching account details']);
    }
    break;

        case 'send_message':
            if ($method !== 'POST') sendError('Method not allowed', 405);
            
            $userId = checkAuth();
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                sendError('Invalid JSON input');
            }
            
            if (empty($input['message']) || empty($input['room_id'])) {
                sendError('Message and room ID are required');
            }
            
            try {
                $db = Database::getInstance()->getConnection();
                $roomId = (int)$input['room_id'];
                
                // Check if user has access to this room
                $accessQuery = "SELECT 1 FROM chat_rooms r 
                              LEFT JOIN room_participants rp ON r.id = rp.room_id AND rp.user_id = :user_id
                              WHERE r.id = :room_id AND (r.is_private = 0 OR rp.user_id IS NOT NULL)";
                
                $accessStmt = $db->prepare($accessQuery);
                $accessStmt->bindParam(':user_id', $userId);
                $accessStmt->bindParam(':room_id', $roomId);
                $accessStmt->execute();
                
                if (!$accessStmt->fetch()) {
                    sendError('Access denied', 403);
                }

                // Insert message
                $query = "INSERT INTO messages (room_id, user_id, message_text, message_type, created_at) 
                         VALUES (:room_id, :user_id, :message_text, :message_type, NOW())";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':room_id', $roomId);
                $stmt->bindParam(':user_id', $userId);
                $stmt->bindParam(':message_text', $input['message']);
                $messageType = $input['type'] ?? 'text';
                $stmt->bindParam(':message_type', $messageType);
                
                if ($stmt->execute()) {
                    $messageId = $db->lastInsertId();
                    
                    // Get the message with user info
                    $msgQuery = "SELECT m.*, u.username, u.full_name 
                               FROM messages m 
                               JOIN users u ON m.user_id = u.id 
                               WHERE m.id = :message_id";
                    
                    $msgStmt = $db->prepare($msgQuery);
                    $msgStmt->bindParam(':message_id', $messageId);
                    $msgStmt->execute();
                    $msg = $msgStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($msg) {
                        $formattedMessage = [
                            'id' => (int)$msg['id'],
                            'author' => $msg['full_name'] ?: $msg['username'],
                            'avatar' => strtoupper(($msg['username'] ?? 'U')[0]),
                            'text' => $msg['message_text'],
                            'time' => date('H:i A', strtotime($msg['created_at'])),
                            'timestamp' => $msg['created_at'],
                            'userId' => (int)$msg['user_id'],
                            'messageType' => $msg['message_type'] ?? 'text'
                        ];
                        
                        sendSuccess([
                            'message' => 'Message sent successfully',
                            'formatted_message' => $formattedMessage
                        ]);
                    } else {
                        sendSuccess(['message' => 'Message sent successfully']);
                    }
                } else {
                    sendError('Failed to send message');
                }
                
            } catch (Exception $e) {
                error_log("Send message error: " . $e->getMessage());
                sendError('Failed to send message: ' . $e->getMessage());
            }
            break;

        case 'get_members':
            $userId = checkAuth();
            $roomId = intval($_GET['room_id'] ?? 0);
            
            if ($roomId <= 0) {
                sendError('Invalid room ID');
            }
            
            try {
                $db = Database::getInstance()->getConnection();
                
                // Check if user has access to this room
                $accessQuery = "SELECT 1 FROM chat_rooms r 
                              LEFT JOIN room_participants rp ON r.id = rp.room_id AND rp.user_id = :user_id
                              WHERE r.id = :room_id AND (r.is_private = 0 OR rp.user_id IS NOT NULL)";
                
                $accessStmt = $db->prepare($accessQuery);
                $accessStmt->bindParam(':user_id', $userId);
                $accessStmt->bindParam(':room_id', $roomId);
                $accessStmt->execute();
                
                if (!$accessStmt->fetch()) {
                    sendError('Access denied', 403);
                }

                // Get room members
                $query = "SELECT u.*, rp.role 
                         FROM room_participants rp 
                         JOIN users u ON rp.user_id = u.id 
                         WHERE rp.room_id = :room_id 
                         ORDER BY rp.role DESC, u.username";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':room_id', $roomId);
                $stmt->execute();
                
                $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $formatted = array_map(function($m) {
                    return [
                        'id' => (int)$m['id'],
                        'name' => $m['full_name'] ?: $m['username'],
                        'username' => $m['username'],
                        'avatar' => strtoupper(($m['username'] ?? 'U')[0]),
                        'online' => (bool)($m['is_online'] ?? false),
                        'role' => $m['role'] ?? 'member',
                        'avatarUrl' => $m['avatar_url'] ?? null
                    ];
                }, $members);

                sendSuccess(['members' => $formatted, 'count' => count($formatted)]);
                
            } catch (Exception $e) {
                error_log("Get members error: " . $e->getMessage());
                sendError('Failed to load members: ' . $e->getMessage());
            }
            break;

        case 'get_recent_messages':
            $userId = checkAuth();
            $roomId = intval($_GET['room_id'] ?? 0);
            
            if ($roomId <= 0) {
                sendError('Invalid room ID');
            }
            
            try {
                if (!$chat->isUserInRoom($userId, $roomId)) {
                    sendError('Access denied', 403);
                }

                $messages = $chat->getRecentMessages($roomId, $_GET['last_time'] ?? null);
                $formatted = array_map(function($m) {
                    return [
                        'id' => (int)$m['id'],
                        'author' => $m['full_name'] ?: $m['username'],
                        'avatar' => strtoupper(($m['username'] ?? 'U')[0]),
                        'text' => $m['message_text'],
                        'time' => date('H:i A', strtotime($m['created_at'])),
                        'timestamp' => $m['created_at'],
                        'userId' => (int)$m['user_id'],
                        'messageType' => $m['message_type'] ?? 'text'
                    ];
                }, $messages);

                sendSuccess(['messages' => $formatted]);
                
            } catch (Exception $e) {
                error_log("Get recent messages error: " . $e->getMessage());
                sendError('Failed to load recent messages');
            }
            break;

        case 'edit_message':
            if ($method !== 'PUT') sendError('Method not allowed', 405);
            
            $userId = checkAuth();
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                sendError('Invalid JSON input');
            }
            
            try {
                $result = $chat->editMessage($input['message_id'], $userId, $input['message']);
                echo json_encode($result);
            } catch (Exception $e) {
                error_log("Edit message error: " . $e->getMessage());
                sendError('Failed to edit message');
            }
            break;

        case 'delete_message':
            if ($method !== 'DELETE') sendError('Method not allowed', 405);
            
            $userId = checkAuth();
            $messageId = $_GET['message_id'] ?? '';
            
            if (empty($messageId)) {
                sendError('Message ID is required');
            }
            
            try {
                $result = $chat->deleteMessage($messageId, $userId);
                echo json_encode($result);
            } catch (Exception $e) {
                error_log("Delete message error: " . $e->getMessage());
                sendError('Failed to delete message');
            }
            break;

        case 'join_room':
            if ($method !== 'POST') sendError('Method not allowed', 405);
            
            $userId = checkAuth();
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                sendError('Invalid JSON input');
            }
            
            if (empty($input['room_id'])) {
                sendError('Room ID is required');
            }
            
            try {
                $success = $chat->addUserToRoom($input['room_id'], $userId, $input['role'] ?? 'member');
                sendSuccess(['message' => $success ? 'Joined room successfully' : 'Failed to join room']);
            } catch (Exception $e) {
                error_log("Join room error: " . $e->getMessage());
                sendError('Failed to join room');
            }
            break;

        case 'get_stats':
            $userId = checkAuth();
            $roomId = intval($_GET['room_id'] ?? 0);
            
            if ($roomId <= 0) {
                sendError('Invalid room ID');
            }
            
            try {
                if (!$chat->isUserInRoom($userId, $roomId)) {
                    sendError('Access denied', 403);
                }
                
                $stats = $chat->getChatStats($roomId);
                sendSuccess(['stats' => $stats]);
            } catch (Exception $e) {
                error_log("Get stats error: " . $e->getMessage());
                sendError('Failed to load statistics');
            }
            break;

        case 'search_rooms':
            $userId = checkAuth();
            $search = $_GET['q'] ?? '';
            
            if (empty($search)) {
                sendSuccess(['rooms' => []]);
            }
            
            try {
                $db = Database::getInstance()->getConnection();
                $query = "SELECT DISTINCT r.*, rp.role 
                          FROM chat_rooms r 
                          LEFT JOIN room_participants rp ON r.id = rp.room_id AND rp.user_id = :user_id
                          WHERE (r.name LIKE :search OR r.description LIKE :search) 
                          AND (r.is_private = 0 OR rp.user_id IS NOT NULL)
                          ORDER BY r.name";

                $stmt = $db->prepare($query);
                $searchPattern = '%' . $search . '%';
                $stmt->bindParam(':search', $searchPattern);
                $stmt->bindParam(':user_id', $userId);
                $stmt->execute();

                $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $formatted = array_map(function($r) {
                    return [
                        'id' => (int)$r['id'],
                        'name' => $r['name'],
                        'description' => $r['description'] ?? '',
                        'avatar' => strtoupper($r['name'][0]),
                        'isPrivate' => (bool)$r['is_private'],
                        'isMember' => !is_null($r['role']),
                        'role' => $r['role']
                    ];
                }, $rooms);

                sendSuccess(['rooms' => $formatted]);
                
            } catch (Exception $e) {
                error_log("Search rooms error: " . $e->getMessage());
                sendError('Failed to search rooms');
            }
            break;

        case 'get_user_info':
            $userId = checkAuth();
            
            try {
                $user = getCurrentUser();
                if (!$user) {
                    sendError('User not found', 404);
                }
                
                sendSuccess(['user' => [
                    'id' => (int)$user['id'],
                    'username' => $user['username'],
                    'fullName' => $user['full_name'],
                    'avatar' => strtoupper(($user['username'] ?? 'U')[0]),
                    'avatarUrl' => $user['avatar_url']
                ]]);
            } catch (Exception $e) {
                error_log("Get user info error: " . $e->getMessage());
                sendError('Failed to load user information');
            }
            break;
        
        case 'logout':
            session_destroy();
            echo json_encode(['success' => true]);
            exit;

        case 'update_online_status':
            if ($method !== 'POST') sendError('Method not allowed', 405);
            
            $userId = checkAuth();
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                sendError('Invalid JSON input');
            }
            
            $isOnline = $input['online'] ?? true;

            try {
                $db = Database::getInstance()->getConnection();
                $stmt = $db->prepare("UPDATE users SET is_online = :is_online, last_seen = NOW() WHERE id = :user_id");
                $stmt->bindParam(':is_online', $isOnline, PDO::PARAM_BOOL);
                $stmt->bindParam(':user_id', $userId);
                $success = $stmt->execute();

                sendSuccess(['message' => $success ? 'Status updated successfully' : 'Failed to update status']);
            } catch (Exception $e) {
                error_log("Update online status error: " . $e->getMessage());
                sendError('Failed to update online status');
            }
            break;

        default:
            sendError('API endpoint not found', 404);
    }

} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    sendError('Internal server error: ' . $e->getMessage(), 500);
}