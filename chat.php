<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Dashboard</title>
    <?php
require_once 'config.php'; // assumes you have a Database class here

class Chat {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getUserRooms($userId) {
        $query = "SELECT r.*, rp.role, 
                         (SELECT COUNT(*) FROM messages m WHERE m.room_id = r.id) AS message_count
                  FROM chat_rooms r
                  JOIN room_participants rp ON rp.room_id = r.id
                  WHERE rp.user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getMessages($roomId, $limit = 50, $offset = 0) {
        $query = "SELECT m.*, u.username, u.full_name 
                  FROM messages m 
                  JOIN users u ON m.user_id = u.id 
                  WHERE m.room_id = :room_id 
                  ORDER BY m.created_at DESC 
                  LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':room_id', $roomId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return array_reverse($stmt->fetchAll());
    }

    public function sendMessage($roomId, $userId, $message, $type = 'text') {
        $query = "INSERT INTO messages (room_id, user_id, message_text, message_type, created_at) 
                  VALUES (:room_id, :user_id, :message, :type, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':room_id', $roomId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':type', $type);

        if ($stmt->execute()) {
            $id = $this->db->lastInsertId();
            $msgData = $this->getMessageById($id);
            return ['success' => true, 'message_data' => $msgData];
        }
        return ['success' => false, 'message' => 'Failed to send message'];
    }

    public function getMessageById($id) {
        $query = "SELECT m.*, u.username, u.full_name 
                  FROM messages m 
                  JOIN users u ON m.user_id = u.id 
                  WHERE m.id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function isUserInRoom($userId, $roomId) {
        $query = "SELECT COUNT(*) FROM room_participants 
                  WHERE user_id = :user_id AND room_id = :room_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':room_id', $roomId);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    public function getRoomParticipants($roomId) {
        $query = "SELECT u.*, rp.role 
                  FROM users u 
                  JOIN room_participants rp ON rp.user_id = u.id 
                  WHERE rp.room_id = :room_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':room_id', $roomId);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function createRoom($name, $description, $creatorId, $isPrivate = false) {
        $query = "INSERT INTO chat_rooms (name, description, created_by, is_private, created_at)
                  VALUES (:name, :description, :creatorId, :isPrivate, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':creatorId', $creatorId);
        $stmt->bindParam(':isPrivate', $isPrivate, PDO::PARAM_BOOL);

        if ($stmt->execute()) {
            $roomId = $this->db->lastInsertId();
            $this->addUserToRoom($roomId, $creatorId, 'admin');
            return ['success' => true, 'room_id' => $roomId];
        }
        return ['success' => false, 'message' => 'Failed to create room'];
    }

    public function addUserToRoom($roomId, $userId, $role = 'member') {
        $query = "INSERT INTO room_participants (room_id, user_id, role) 
                  VALUES (:room_id, :user_id, :role)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':room_id', $roomId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':role', $role);
        return $stmt->execute();
    }

    public function editMessage($messageId, $userId, $newMessage) {
        $query = "UPDATE messages 
                  SET message_text = :message, is_edited = 1 
                  WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':message', $newMessage);
        $stmt->bindParam(':id', $messageId);
        $stmt->bindParam(':user_id', $userId);
        if ($stmt->execute()) {
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Failed to edit message'];
    }

    public function deleteMessage($messageId, $userId) {
        $query = "DELETE FROM messages WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $messageId);
        $stmt->bindParam(':user_id', $userId);
        if ($stmt->execute()) {
            return ['success' => true];
        }
        return ['success' => false, 'message' => 'Failed to delete message'];
    }

    public function getRecentMessages($roomId, $lastTime) {
        $query = "SELECT m.*, u.username, u.full_name 
                  FROM messages m 
                  JOIN users u ON m.user_id = u.id 
                  WHERE m.room_id = :room_id AND m.created_at > :last_time
                  ORDER BY m.created_at ASC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':room_id', $roomId);
        $stmt->bindParam(':last_time', $lastTime);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getChatStats($roomId) {
        $query = "SELECT COUNT(*) AS total_messages 
                  FROM messages 
                  WHERE room_id = :room_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':room_id', $roomId);
        $stmt->execute();
        return $stmt->fetch();
    }
}
?>
<style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            color: #333;
            height: 100vh;
            overflow: hidden;
        }
        /* Account Modal Styles */
.account-profile {
    display: flex;
    flex-direction: column;
    gap: 25px;
    padding: 20px 0;
}

.account-avatar-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
}

.account-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: bold;
    color: white;
    text-transform: uppercase;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border: 3px solid white;
}

.account-username {
    font-size: 18px;
    font-weight: 600;
    color: #495057;
    background: #f8f9fa;
    padding: 6px 16px;
    border-radius: 20px;
    border: 1px solid #dee2e6;
}

.account-info-grid {
    display: grid;
    gap: 16px;
}

.account-info-item {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 16px;
    border: 1px solid #e9ecef;
    transition: all 0.2s ease;
}

.account-info-item:hover {
    background: #e9ecef;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.account-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #495057;
    font-size: 14px;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.account-label .icon {
    font-size: 16px;
}

.account-value {
    color: #212529;
    font-size: 16px;
    font-weight: 500;
    word-break: break-word;
    background: white;
    padding: 8px 12px;
    border-radius: 4px;
    border: 1px solid #dee2e6;
}

.loading {
    text-align: center;
    color: #6c757d;
    padding: 40px 0;
    font-size: 16px;
}

.error {
    color: #dc3545;
    text-align: center;
    padding: 20px;
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    border-radius: 8px;
    font-size: 16px;
}

/* Modal improvements */
.modal-content {
    max-width: 500px;
    width: 90%;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    overflow: hidden;
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    text-align: center;
    font-size: 20px;
    font-weight: 600;
}

#account-details {
    padding: 0 20px;
    max-height: 500px;
    overflow-y: auto;
}

.modal-buttons {
    padding: 20px;
    text-align: center;
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
}

.btn {
    padding: 10px 24px;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-secondary {
    background: #6c757d;
    color: white;
    border: none;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-1px);
}

        .chat-container {
            display: flex;
            height: 100vh;
        }

        /* Left Sidebar */
        .left-sidebar {
            width: 80px;
            background: #2c3e50;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 0;
            gap: 30px;
        }

        .sidebar-item {
            width: 50px;
            height: 50px;
            background: #34495e;
            border-radius: 30%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            transition: background 0.3s;
            font-size: 12px;
            text-align: center;
            line-height: 1.2;
        }

        .sidebar-item:hover {
            background: #3498db;
        }

        .sidebar-item.active {
            background: #3498db;
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
        }

        /* Top Search Bar */
        .search-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
            background: white;
        }

        .search-box {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            font-size: 14px;
            background: #f8f9fa;
        }

        .search-box:focus {
            outline: none;
            border-color: #3498db;
        }

        /* Rooms List Area */
        .rooms-content {
            flex: 1;
            overflow-y: auto;
            padding: 10px 0;
        }

        .room-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s;
        }

        .room-item:hover {
            background: #f8f9fa;
        }

        .room-item.active {
            background: #e3f2fd;
            border-right: 3px solid #3498db;
        }

        .room-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #3498db;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .room-info {
            flex: 1;
            min-width: 0;
        }

        .room-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .room-name {
            font-weight: 600;
            font-size: 16px;
            color: #2c3e50;
        }

        .room-time {
            font-size: 12px;
            color: #7f8c8d;
        }

        .room-preview {
            font-size: 14px;
            color: #7f8c8d;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Right Sidebar - Members */
        .right-sidebar {
            width: 250px;
            background: white;
            border-left: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
        }

        .members-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            background: #f8f9fa;
        }

        .members-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .members-count {
            font-size: 14px;
            color: #7f8c8d;
        }

        .members-list {
            flex: 1;
            overflow-y: auto;
            padding: 15px 0;
        }

        .member-item {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .member-item:hover {
            background: #f8f9fa;
        }

        .member-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #95a5a6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
            margin-right: 12px;
            position: relative;
        }

        .online-dot {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 10px;
            height: 10px;
            background: #27ae60;
            border: 2px solid white;
            border-radius: 50%;
        }

        .member-name {
            font-size: 14px;
            color: #2c3e50;
        }

        /* Chat Interface */
        .chat-interface {
            position: fixed;
            top: 0;
            left: 80px;
            right: 250px;
            bottom: 0;
            background: white;
            display: none;
            flex-direction: column;
            z-index: 1000;
        }

        .chat-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            background: #f8f9fa;
        }

        .chat-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #fafafa;
        }

        .message {
            display: flex;
            margin-bottom: 20px;
            align-items: flex-start;
        }

        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #3498db;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .message-content {
            flex: 1;
        }

        .message-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
        }

        .message-author {
            font-weight: 600;
            color: #2c3e50;
        }

        .message-time {
            font-size: 12px;
            color: #7f8c8d;
        }

        .message-text {
            color: #2c3e50;
            line-height: 1.5;
        }

        .chat-input-area {
            padding: 20px;
            border-top: 1px solid #e0e0e0;
            background: white;
        }

        .chat-input-form {
            display: flex;
            gap: 10px;
        }

        .chat-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-size: 14px;
            resize: none;
            height: 45px;
        }

        .chat-input:focus {
            outline: none;
            border-color: #3498db;
        }

        .send-button {
            padding: 12px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s;
        }

        .send-button:hover {
            background: #2980b9;
        }

        .send-button:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }

        /* Welcome State */
        .welcome-state {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #7f8c8d;
        }

        .welcome-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #2c3e50;
        }

        /* Loading States */
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #7f8c8d;
        }

        /* Error States */
        .error {
            background: #e74c3c;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin: 10px;
        }

        /* Modal for creating new group */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 400px;
            max-width: 90%;
        }

        .modal-header {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #2c3e50;
        }

        .form-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-input:focus {
            outline: none;
            border-color: #3498db;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .btn:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }
    </style>
   
</head>
<body>
    <div class="chat-container">
        <!-- Left Sidebar -->
        <div class="left-sidebar">
            <div class="sidebar-item active" data-section="inbox">
                Inbox
            </div>
            <div class="sidebar-item" data-section="new-group" onclick="showNewGroupModal()">
                New Group
            </div>
            <div class="sidebar-item" data-section="account" onclick="showAccountOptions()">
                Account
            </div>
            <div class="sidebar-item " data-section="friends">
                Friends
            </div>
            <!-- ...existing sidebar items... -->
            <div class="sidebar-item" id="logout-btn" style="margin-top:auto; background:#e74c3c;">
             Log Out
             </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Search Header -->
            <div class="search-header">
                <input type="text" class="search-box" placeholder="Search conversations..." id="search-input">
            </div>

            <!-- Rooms List -->
            <div class="rooms-content" id="rooms-list">
                <div class="loading">Loading rooms...</div>
            </div>
        </div>

        <!-- Right Sidebar - Members -->
        <div class="right-sidebar" style="display:none;">
           <div class="members-header" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
        <div class="members-title">Members</div>
        <div class="members-count" id="members-count">0 members</div>
    </div>
    <button class="btn btn-primary" id="add-member-btn" style="font-size: 13px; padding: 6px 14px;">+ Add</button>
</div>
            <div class="members-list" id="members-list">
                <div style="padding: 20px; text-align: center; color: #7f8c8d;">
                    Select a room to view members
                </div>
            </div>
        </div>

        <!-- Chat Interface Overlay -->
        <div class="chat-interface" id="chat-interface">
            <div class="chat-header">
                <div class="chat-title" id="chat-title">Room Name</div>
            </div>
            <!--a button to go back to the inbox -->
             <button id="back-to-inbox" class="btn btn-secondary">← Back to Inbox</button>
            <div class="chat-messages" id="chat-messages">
                <!-- Messages will be loaded here -->
            </div>
            <div class="chat-input-area">
                <form class="chat-input-form" id="chat-form">
                    <textarea class="chat-input" id="chat-input" placeholder="Type a message..." rows="1"></textarea>
                    <button type="submit" class="send-button" id="send-button">Send</button>
                </form>
            </div>
        </div>
    </div>

<!-- New Group Modal -->
    <div class="modal" id="new-group-modal">
        <div class="modal-content">
            <div class="modal-header">Create New Group</div>
            <form id="create-group-form">
                <div class="form-group">
                    <label class="form-label">Group Name</label>
                    <input type="text" class="form-input" id="group-name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description (optional)</label>
                    <input type="text" class="form-input" id="group-description">
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeNewGroupModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Group</button>
                </div>
            </form>
        </div>
    </div>


    <!-- Account Modal -->
    <div class="modal" id="account-modal">
        <div class="modal-content">
            <div class="modal-header">Account Details</div>
            <div id="account-details" style="padding: 10px 0;">
                <!-- Details will be loaded here -->
            </div>
            <div class="modal-buttons">
                <button type="button" class="btn btn-secondary" onclick="closeAccountModal()">Close</button>
            </div>
        </div>
    </div>
    <!-- Friends Modal -->
<div class="modal" id="friends-modal">
    <div class="modal-content">
        <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center;">
            <span>Friends</span>
            <button class="btn btn-primary" id="add-friend-btn" style="font-size: 14px;">+ Add Friend</button>
        </div>
        <div id="friends-list" style="padding: 10px 0; min-height: 80px;">
            <!-- Friends will be loaded here -->
        </div>
        <div class="modal-buttons">
            <button type="button" class="btn btn-secondary" onclick="closeFriendsModal()">Close</button>
        </div>
    </div>
</div>
<!-- Add Member Modal -->
<div class="modal" id="add-member-modal">
    <div class="modal-content">
        <div class="modal-header">Add Members to Group</div>
        <div id="add-member-list" style="max-height: 250px; overflow-y: auto; margin-bottom: 15px;">
            <!-- Friends will be listed here -->
        </div>
        <div class="modal-buttons">
            <button type="button" class="btn btn-secondary" onclick="closeAddMemberModal()">Cancel</button>
        </div>
    </div>
</div>
<!-- Add Friend Modal -->
<div class="modal" id="add-friend-modal">
    <div class="modal-content">
        <div class="modal-header">Add Friend</div>
        <input type="text" id="friend-search-input" class="form-input" placeholder="Search users by name or email..." style="margin-bottom: 15px;">
        <div id="friend-search-results" style="min-height: 60px;"></div>
        <div class="modal-buttons">
            <button type="button" class="btn btn-secondary" onclick="closeAddFriendModal()">Cancel</button>
        </div>
    </div>
</div>

    <!-- Basic JS -->
    <script>
let currentRoomId = null;
let currentRoomType = null;
let currentFriendId = null;

// Load rooms from backend
async function loadRooms() {
    const roomsList = document.getElementById('rooms-list');
    roomsList.innerHTML = '<div class="loading">Loading rooms...</div>';
    try {
        const res = await fetch('chat_api.php?action=get_rooms');
        const data = await res.json();
        if (!data.success) throw new Error(data.message);

        roomsList.innerHTML = '';
        if (data.rooms.length === 0) {
            roomsList.innerHTML = '<div class="welcome-state"><h3>No rooms yet</h3><p>Create a new group to start chatting!</p></div>';
            return;
        }
        data.rooms.forEach(room => {
            const roomDiv = document.createElement('div');
            roomDiv.className = 'room-item';
            roomDiv.innerHTML = `
                <div class="room-avatar">${room.avatar}</div>
                <div class="room-info">
                    <div class="room-header">
                        <div class="room-name">${room.name}</div>
                        <div class="room-time">${room.lastTime || ''}</div>
                    </div>
                    <div class="room-preview">${room.lastMessage || ''}</div>
                </div>
            `;
            roomDiv.onclick = () => selectRoom(room);
            roomsList.appendChild(roomDiv);
        });
    } catch (e) {
        roomsList.innerHTML = `<div class="error">Failed to load rooms: ${e.message}</div>`;
    }
}

// Select a room and load messages/members
async function selectRoom(room) {
    currentRoomId = room.id;
    currentRoomType = room.type;
    currentFriendId = room.type === 'dm' ? room.friend_id : null;
    document.getElementById('chat-title').textContent = room.name;
    document.getElementById('chat-interface').style.display = 'flex';
    // Show/hide right sidebar based on room type
    const rightSidebar = document.querySelector('.right-sidebar');
    const chatInterface = document.getElementById('chat-interface');
    if (room.type === 'group') {
        rightSidebar.style.display = 'flex';
        chatInterface.style.right = '250px';
        await loadMembers();
    } else {
        rightSidebar.style.display = 'none';
        chatInterface.style.right = '0';
    }

    // Highlight selected room
    document.querySelectorAll('.room-item').forEach(item => item.classList.remove('active'));
    const roomItems = document.querySelectorAll('.room-item');
    roomItems.forEach(item => {
        if (item.querySelector('.room-name').textContent === room.name) {
            item.classList.add('active');
        }
    });

    if (room.type === 'dm') {
        await loadDmMessages(room.friend_id);
    } else {
        await loadMessages();
        await loadMembers();
    }
}

// Load DM messages
async function loadDmMessages(friendId) {
    const messagesContainer = document.getElementById('chat-messages');
    messagesContainer.innerHTML = '<div class="loading">Loading messages...</div>';
    try {
        const res = await fetch(`chat_api.php?action=get_dm_messages&friend_id=${friendId}`);
        const data = await res.json();
        if (!data.success) throw new Error(data.message);

        messagesContainer.innerHTML = '';
        data.messages.forEach(msg => {
            const msgDiv = document.createElement('div');
            msgDiv.className = 'message';
            msgDiv.innerHTML = `
                <div class="message-avatar">${msg.avatar}</div>
                <div class="message-content">
                    <div class="message-header">
                        <div class="message-author">${msg.author}</div>
                        <div class="message-time">${msg.time}</div>
                    </div>
                    <div class="message-text">${msg.text}</div>
                </div>
            `;
            messagesContainer.appendChild(msgDiv);
        });
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    } catch (e) {
        messagesContainer.innerHTML = `<div class="error">Failed to load messages: ${e.message}</div>`;
    }
}

// Logout button handler
document.getElementById('logout-btn').addEventListener('click', async function() {
    try {
        const res = await fetch('chat_api.php?action=logout', { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            window.location.href = 'login.php'; // Redirect to login page
        } else {
            alert(data.message || 'Logout failed');
        }
    } catch (e) {
        alert('Logout failed');
    }
});

// Load messages for the selected room
async function loadMessages() {
    const messagesContainer = document.getElementById('chat-messages');
    messagesContainer.innerHTML = '<div class="loading">Loading messages...</div>';
    try {
        const res = await fetch(`chat_api.php?action=get_messages&room_id=${currentRoomId}`);
        const data = await res.json();
        if (!data.success) throw new Error(data.message);

        messagesContainer.innerHTML = '';
        data.messages.forEach(msg => {
            const msgDiv = document.createElement('div');
            msgDiv.className = 'message';
            msgDiv.innerHTML = `
                <div class="message-avatar">${msg.avatar}</div>
                <div class="message-content">
                    <div class="message-header">
                        <div class="message-author">${msg.author}</div>
                        <div class="message-time">${msg.time}</div>
                    </div>
                    <div class="message-text">${msg.text}</div>
                </div>
            `;
            messagesContainer.appendChild(msgDiv);
        });
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    } catch (e) {
        messagesContainer.innerHTML = `<div class="error">Failed to load messages: ${e.message}</div>`;
    }
}

// Load members for the selected room
async function loadMembers() {
    const membersList = document.getElementById('members-list');
    const membersCount = document.getElementById('members-count');
    membersList.innerHTML = '<div class="loading">Loading members...</div>';
    try {
        const res = await fetch(`chat_api.php?action=get_members&room_id=${currentRoomId}`);
        const data = await res.json();
        if (!data.success) throw new Error(data.message);

        membersList.innerHTML = '';
        membersCount.textContent = `${data.count} members`;
        data.members.forEach(member => {
            const memberDiv = document.createElement('div');
            memberDiv.className = 'member-item';
            memberDiv.innerHTML = `
                <div class="member-avatar">
                    ${member.avatar}
                    ${member.online ? '<div class="online-dot"></div>' : ''}
                </div>
                <div class="member-name">${member.name}</div>
            `;
            membersList.appendChild(memberDiv);
        });
    } catch (e) {
        membersList.innerHTML = `<div class="error">Failed to load members: ${e.message}</div>`;
        membersCount.textContent = '0 members';
    }
}

// Handle sending a message
document.getElementById('chat-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const input = document.getElementById('chat-input');
    const text = input.value.trim();
    if (!text || !currentRoomId) return;
    document.getElementById('send-button').disabled = true;
    try {
        let res, data;
        if (currentRoomType === 'dm') {
            res = await fetch('chat_api.php?action=send_dm_message', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ friend_id: currentFriendId, message: text })
            });
            data = await res.json();
            if (data.success) {
                input.value = '';
                await loadDmMessages(currentFriendId);
            } else {
                alert(data.message || 'Failed to send message');
            }
        } else {
            res = await fetch('chat_api.php?action=send_message', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ room_id: currentRoomId, message: text })
            });
            data = await res.json();
            if (data.success) {
                input.value = '';
                await loadMessages();
            } else {
                alert(data.message || 'Failed to send message');
            }
        }
    } finally {
        document.getElementById('send-button').disabled = false;
    }
});
//this is where change has been put
// Show Friends Modal
// Add event listener for inbox sidebar button
document.querySelector('.sidebar-item[data-section="inbox"]').addEventListener('click', function() {
    // Hide chat interface and show rooms list
    document.getElementById('chat-interface').style.display = 'none';
    document.getElementById('rooms-list').style.display = 'block';
    // Hide the right sidebar when in inbox
    document.querySelector('.right-sidebar').style.display = 'none';
    // Clear the active room
    currentRoomId = null;
    currentRoomType = null;
    currentFriendId = null;
    // Remove active class from all room items
    document.querySelectorAll('.room-item').forEach(item => item.classList.remove('active'));
    // Make inbox button active
    document.querySelectorAll('.sidebar-item').forEach(item => item.classList.remove('active'));
    this.classList.add('active');
});
function closeFriendsModal() {
    document.getElementById('friends-modal').style.display = 'none';
    // If no room is selected, hide right sidebar
    if (!currentRoomId || currentRoomType !== 'group') {
        document.querySelector('.right-sidebar').style.display = 'none';
    }
}

// Load Friends List
async function loadFriends() {
    const listDiv = document.getElementById('friends-list');
    listDiv.innerHTML = '<div class="loading">Loading friends...</div>';
    try {
        const res = await fetch('chat_api.php?action=get_friends');
        const data = await res.json();
        if (data.success && data.friends.length > 0) {
            listDiv.innerHTML = '';
            data.friends.forEach(friend => {
                const friendDiv = document.createElement('div');
                friendDiv.className = 'account-info-item';
                friendDiv.innerHTML = `
                    <div class="account-label">
                        <i class="icon">👤</i> ${friend.full_name || friend.username}
                    </div>
                    <div class="account-value">${friend.email}</div>
                `;
                friendDiv.style.cursor = 'pointer';
                friendDiv.onclick = () => openChatWithFriend(friend);
                listDiv.appendChild(friendDiv);
            });
        } else {
            listDiv.innerHTML = `<div class="loading">No friends yet, add some!</div>`;
        }
    } catch (e) {
        listDiv.innerHTML = `<div class="error">Failed to load friends</div>`;
    }
}

// Add Friend Modal
document.getElementById('add-friend-btn').addEventListener('click', function() {
    document.getElementById('add-friend-modal').style.display = 'flex';
    document.getElementById('friend-search-input').value = '';
    document.getElementById('friend-search-results').innerHTML = '';
});
function closeAddFriendModal() {
    document.getElementById('add-friend-modal').style.display = 'none';
}

//Functionality to search for friends 
//You neeed to connect to the database to add existing users to an array that will be used to search
document.getElementById('friend-search-input').addEventListener('input', async function() {
    const query = this.value.trim();
    const resultsDiv = document.getElementById('friend-search-results');
    if (!query) {
        resultsDiv.innerHTML = '';
        return;
    }
    resultsDiv.innerHTML = '<div class="loading">Searching...</div>';
    try {
        console.log('Searching for:', query);
        const res = await fetch('chat_api.php?action=search_users&query=' + encodeURIComponent(query));
        const data = await res.json();
        console.log('Search response:', data);
        if (data.success && data.users.length > 0) {
            resultsDiv.innerHTML = '';
            data.users.forEach(user => {
                const userDiv = document.createElement('div');
                userDiv.className = 'account-info-item';
                userDiv.innerHTML = `
                    <div class="account-label">
                        <i class="icon">👤</i> ${user.full_name || user.username}
                    </div>
                    <button class="btn btn-primary" onclick="addFriend(${user.id}, this)">Add Friend</button>
                `;
                resultsDiv.appendChild(userDiv);
            });
        } else {
            resultsDiv.innerHTML = '<div class="loading">No users found</div>';
        }
    } catch (e) {
        console.error('Search users error:', e);
        resultsDiv.innerHTML = `<div class="error">Failed to search users</div>`;
    }
});
// Show Add Member Modal
document.getElementById('add-member-btn').addEventListener('click', function() {
    document.getElementById('add-member-modal').style.display = 'flex';
    loadAddableFriends();
});
function closeAddMemberModal() {
    document.getElementById('add-member-modal').style.display = 'none';
}

// Load friends not already in the group
async function loadAddableFriends() {
    const listDiv = document.getElementById('add-member-list');
    listDiv.innerHTML = '<div class="loading">Loading friends...</div>';
    try {
        // Fetch your friends
        const res = await fetch('chat_api.php?action=get_friends');
        const data = await res.json();
        if (data.success && data.friends.length > 0) {
            // Optionally, filter out friends already in the group
            // For demo, just show all friends:
            listDiv.innerHTML = '';
            data.friends.forEach(friend => {
                const friendDiv = document.createElement('div');
                friendDiv.className = 'account-info-item';
                friendDiv.style.display = 'flex';
                friendDiv.style.justifyContent = 'space-between';
                friendDiv.style.alignItems = 'center';
                friendDiv.style.marginBottom = '8px';
                friendDiv.innerHTML = `
                    <span>
                        <i class="icon">👤</i> ${friend.full_name || friend.username}
                    </span>
                    <button class="btn btn-primary" style="font-size:12px; padding:4px 10px;" onclick="addMemberToGroup(${friend.id}, this)">Add</button>
                `;
                listDiv.appendChild(friendDiv);
            });
        } else {
            listDiv.innerHTML = '<div class="loading">No friends to add.</div>';
        }
    } catch (e) {
        listDiv.innerHTML = '<div class="error">Failed to load friends</div>';
    }
}

// Add member to group (call your backend)
function addMemberToGroup(friendId, btn) {
    btn.disabled = true;
    btn.textContent = 'Adding...';
    // TODO: Replace with your API call to add member to group
    // Example:
    fetch('chat_api.php?action=add_member_to_group', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ room_id: currentRoomId, user_id: friendId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            btn.textContent = 'Added!';
            // Optionally refresh members list
            loadMembers();
        } else {
            btn.textContent = 'Add';
            alert(data.message || 'Failed to add member');
        }
        btn.disabled = false;
    })
    .catch(() => {
        btn.textContent = 'Add';
        btn.disabled = false;
        alert('Failed to add member');
    });
}



// Add friend function
async function addFriend(userId, btn) {
    btn.disabled = true;
    btn.textContent = 'Adding...';
    try {
        const res = await fetch('chat_api.php?action=add_friend', { // <-- FIXED HERE
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ user_id: userId })
        });
        const data = await res.json();
        if (data.success) {
            btn.textContent = 'Added!';
            loadFriends();
        } else {
            btn.textContent = 'Add';
            alert(data.message || 'Failed to add friend');
        }
    } catch (e) {
        btn.textContent = 'Add';
        alert('Failed to add friend');
    }
}

// Open chat with friend (you can implement this to open a DM room)
function openChatWithFriend(friend) {
    // You can implement logic to open a direct chat room with this friend
    alert('Open chat with ' + (friend.full_name || friend.username));
}

// Handle creating a new group
document.getElementById('create-group-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const name = document.getElementById('group-name').value.trim();
    const desc = document.getElementById('group-description').value.trim();
    if (!name) return;
    try {
        const res = await fetch('chat_api.php?action=create_room', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ name, description: desc })
        });
        const data = await res.json();
        if (data.success) {
            closeNewGroupModal();
            await loadRooms();
        } else {
            alert(data.message || 'Failed to create group');
        }
    } catch (e) {
        alert('Failed to create group: ' + e.message);
    }
});

function showAccountOptions() {
    document.getElementById('account-modal').style.display = 'flex';
    loadAccountDetails();
}
function closeAccountModal() {
    document.getElementById('account-modal').style.display = 'none';
    // If no room is selected, hide right sidebar
    if (!currentRoomId || currentRoomType !== 'group') {
        document.querySelector('.right-sidebar').style.display = 'none';
    }
}
// Enhanced function to load account details
async function loadAccountDetails() {
    const detailsDiv = document.getElementById('account-details');
    detailsDiv.innerHTML = '<div class="loading">Loading account information...</div>';
    
    try {
        const res = await fetch('chat_api.php?action=get_account');
        const data = await res.json();
        
        if (data.success) {
            const user = data.user;
            
            // Generate avatar (first letter of name or use avatar_url if available)
            const avatarContent = user.avatar_url ? 
                `<img src="${user.avatar_url}" alt="Profile Picture" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">` : 
                generateAvatar(user.full_name || user.username);
            
            // Format the date nicely
            const formattedDate = formatDate(user.created_at);
            
            detailsDiv.innerHTML = `
                <div class="account-profile">
                    <div class="account-avatar-section">
                        <div class="account-avatar">
                            ${avatarContent}
                        </div>
                        <div class="account-username">@${user.username}</div>
                    </div>
                    
                    <div class="account-info-grid">
                        <div class="account-info-item">
                            <div class="account-label">
                                <i class="icon">👤</i>
                                Full Name
                            </div>
                            <div class="account-value">${user.full_name || 'Not provided'}</div>
                        </div>
                        
                        <div class="account-info-item">
                            <div class="account-label">
                                <i class="icon">📧</i>
                                Email Address
                            </div>
                            <div class="account-value">${user.email}</div>
                        </div>
                        
                        <div class="account-info-item">
                            <div class="account-label">
                                <i class="icon">🗓️</i>
                                Member Since
                            </div>
                            <div class="account-value">${formattedDate}</div>
                        </div>
                        
                        <div class="account-info-item">
                            <div class="account-label">
                                <i class="icon">🆔</i>
                                User ID
                            </div>
                            <div class="account-value">#${user.id}</div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            detailsDiv.innerHTML = `<div class="error">❌ ${data.message}</div>`;
        }
    } catch (e) {
        console.error('Error loading account details:', e);
        detailsDiv.innerHTML = `<div class="error">❌ Failed to load account information</div>`;
    }
}

// Helper function to generate avatar from name
function generateAvatar(name) {
    if (!name) return '?';
    return name.charAt(0).toUpperCase();
}

// Helper function to format date nicely
function formatDate(dateString) {
    if (!dateString) return 'Unknown';
    
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    // Format the date
    const formattedDate = date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    // Add "days ago" info
    if (diffDays === 0) {
        return `${formattedDate} (Today)`;
    } else if (diffDays === 1) {
        return `${formattedDate} (Yesterday)`;
    } else if (diffDays < 30) {
        return `${formattedDate} (${diffDays} days ago)`;
    } else {
        return formattedDate;
    }
}
  document.getElementById('new-group-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeNewGroupModal();
            }
        });
// Function to show the new group modal
function showNewGroupModal() {
    const modal = document.getElementById('new-group-modal');
    if (modal) {
        modal.style.display = 'block';
    }
}

// Function to close the new group modal
function closeNewGroupModal() {
    const modal = document.getElementById('new-group-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}
//add an event listener that shows you the chat messages again when you press inbox
// Option A: Wait for DOM to load
document.addEventListener('DOMContentLoaded', function() {
    // Add event listener for inbox button
    const inboxButton = document.getElementById('inbox-button');
    if (inboxButton) {
        inboxButton.addEventListener('click', function() {
            document.getElementById('chat-messages').style.display = 'block';
        });
    }
    //function to add functionality to the back to inbox button
   const backButton = document.getElementById('back-to-inbox');
if (backButton) {
    backButton.addEventListener('click', function() {
        document.getElementById('chat-interface').style.display = 'none';
        document.getElementById('rooms-list').style.display = 'block';
        // Hide the right sidebar when going back to inbox
        document.querySelector('.right-sidebar').style.display = 'none';
        // Clear the active room
        currentRoomId = null;
        currentRoomType = null;
        currentFriendId = null;
        // Remove active class from all room items
        document.querySelectorAll('.room-item').forEach(item => item.classList.remove('active'));
    });
}


    // Add event listener for new group modal
    const newGroupModal = document.getElementById('new-group-modal');
    if (newGroupModal) {
        newGroupModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeNewGroupModal();
            }
        });
    }
});
// Initial load
document.addEventListener('DOMContentLoaded', loadRooms);
    </script>
</body>
</html>