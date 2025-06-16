<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Dashboard</title>
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
            border-radius: 50%;
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

        /* Chat Interface (appears when room selected) */
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

        /* Loading and Error States */
        .loading {
            text-align: center;
            padding: 10px;
            color: #7f8c8d;
        }

        .error {
            color: red;
            text-align: center;
            padding: 10px;
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
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Search Header -->
            <div class="search-header">
                <input type="text" class="search-box" placeholder="Search conversations..." id="search-input">
            </div>

            <!-- Rooms List -->
            <div class="rooms-content" id="rooms-list">
                <!-- Initial welcome state -->
                <div class="welcome-state" id="welcome-state">
                    <h3>Welcome to Chat</h3>
                    <p>Select "New Group" to create a chat room or search for existing conversations</p>
                </div>
            </div>
        </div>

        <!-- Right Sidebar - Members -->
        <div class="right-sidebar">
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
            <div class="chat-messages" id="chat-messages">
                <!-- Messages will be loaded here -->
            </div>
            <div class="chat-input-area">
                <form class="chat-input-form" id="chat-form">
                    <textarea class="chat-input" id="chat-input" placeholder="Type a message..." rows="1"></textarea>
                    <button type="submit" class="send-button">Send</button>
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

    <script>
        let currentRoomId = null;
        let currentRoomName = '';

        // Sample data for demonstration
        const sampleRooms = [
            {
                id: 1,
                name: 'General Discussion',
                lastMessage: 'Hey everyone, how are you?',
                lastTime: '10:30',
                avatar: 'G',
                unread: 2
            },
            {
                id: 2,
                name: 'Development Team',
                lastMessage: 'The new feature is ready for testing',
                lastTime: '09:15',
                avatar: 'D',
                unread: 0
            },
            {
                id: 3,
                name: 'Project Alpha',
                lastMessage: 'Meeting scheduled for tomorrow',
                lastTime: '16:45',
                avatar: 'P',
                unread: 1
            }
        ];

        const sampleMembers = [
            { id: 1, name: 'John Doe', avatar: 'J', online: true },
            { id: 2, name: 'Jane Smith', avatar: 'J', online: true },
            { id: 3, name: 'Mike Johnson', avatar: 'M', online: false },
            { id: 4, name: 'Sarah Wilson', avatar: 'S', online: true }
        ];

        const sampleMessages = [
            {
                id: 1,
                author: 'John Doe',
                avatar: 'J',
                text: 'Hello everyone! How are you doing today?',
                time: '10:30 AM'
            },
            {
                id: 2,
                author: 'Jane Smith',
                avatar: 'J',
                text: 'Hi John! I\'m doing great, thanks for asking.',
                time: '10:32 AM'
            },
            {
                id: 3,
                author: 'Mike Johnson',
                avatar: 'M',
                text: 'Same here! Just finished the morning standup.',
                time: '10:35 AM'
            }
        ];

        // Initialize the application
        function init() {
            loadRooms();
            setupEventListeners();
        }

        // Setup event listeners
        function setupEventListeners() {
            // Create group form
            document.getElementById('create-group-form').addEventListener('submit', handleCreateGroup);
            
            // Chat form
            document.getElementById('chat-form').addEventListener('submit', handleSendMessage);
            
            // Search input
            document.getElementById('search-input').addEventListener('input', handleSearch);
            
            // Auto-resize textarea
            const textarea = document.getElementById('chat-input');
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 100) + 'px';
            });

            // Close modal when clicking outside
            document.getElementById('new-group-modal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeNewGroupModal();
                }
            });

            // Show Add Member Modal
            document.getElementById('add-member-btn').addEventListener('click', function() {
    document.getElementById('add-member-modal').style.display = 'flex';
    loadAddableFriends();
});
        }

        // Load rooms into the main area
        function loadRooms() {
            const roomsList = document.getElementById('rooms-list');
            const welcomeState = document.getElementById('welcome-state');
            
            if (sampleRooms.length === 0) {
                welcomeState.style.display = 'flex';
                return;
            }
            
            welcomeState.style.display = 'none';
            roomsList.innerHTML = '';
            
            sampleRooms.forEach(room => {
                const roomElement = document.createElement('div');
                roomElement.className = 'room-item';
                roomElement.onclick = () => selectRoom(room);
                
                roomElement.innerHTML = `
                    <div class="room-avatar">${room.avatar}</div>
                    <div class="room-info">
                        <div class="room-header">
                            <div class="room-name">${room.name}</div>
                            <div class="room-time">${room.lastTime}</div>
                        </div>
                        <div class="room-preview">${room.lastMessage}</div>
                    </div>
                `;
                
                roomsList.appendChild(roomElement);
            });
        }

        // Select a room and show chat interface
        function selectRoom(room) {
            // Remove active class from all rooms
            document.querySelectorAll('.room-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Add active class to selected room
            event.currentTarget.classList.add('active');
            
            currentRoomId = room.id;
            currentRoomName = room.name;
            
            // Show chat interface
            document.getElementById('chat-interface').style.display = 'flex';
            document.getElementById('chat-title').textContent = room.name;
            
            // Load messages and members
            loadMessages();
            loadMembers();
        }

        // Load messages for the selected room
        function loadMessages() {
            const messagesContainer = document.getElementById('chat-messages');
            messagesContainer.innerHTML = '';
            
            sampleMessages.forEach(message => {
                const messageElement = document.createElement('div');
                messageElement.className = 'message';
                
                messageElement.innerHTML = `
                    <div class="message-avatar">${message.avatar}</div>
                    <div class="message-content">
                        <div class="message-header">
                            <div class="message-author">${message.author}</div>
                            <div class="message-time">${message.time}</div>
                        </div>
                        <div class="message-text">${message.text}</div>
                    </div>
                `;
                
                messagesContainer.appendChild(messageElement);
            });
            
            // Scroll to bottom
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Load members for the selected room
        function loadMembers() {
            const membersList = document.getElementById('members-list');
            const membersCount = document.getElementById('members-count');
            
            membersList.innerHTML = '';
            membersCount.textContent = `${sampleMembers.length} members`;
            
            sampleMembers.forEach(member => {
                const memberElement = document.createElement('div');
                memberElement.className = 'member-item';
                
                memberElement.innerHTML = `
                    <div class="member-avatar">
                        ${member.avatar}
                        ${member.online ? '<div class="online-dot"></div>' : ''}
                    </div>
                    <div class="member-name">${member.name}</div>
                `;
                
                membersList.appendChild(memberElement);
            });
        }

        // Handle creating a new group
        function handleCreateGroup(e) {
            e.preventDefault();
            
            const groupName = document.getElementById('group-name').value.trim();
            const groupDescription = document.getElementById('group-description').value.trim();
            
            if (!groupName) return;
            
            // TODO: Send to your PHP API
            console.log('Creating group:', { name: groupName, description: groupDescription });
            
            // Close modal and refresh rooms
            closeNewGroupModal();
            
            // Add new room to sample data (in real app, this would come from server)
            const newRoom = {
                id: sampleRooms.length + 1,
                name: groupName,
                lastMessage: 'Group created',
                lastTime: 'now',
                avatar: groupName.charAt(0).toUpperCase(),
                unread: 0
            };
            sampleRooms.unshift(newRoom);
            
            loadRooms();
        }

        // Handle sending a message
        function handleSendMessage(e) {
            e.preventDefault();
            
            const messageInput = document.getElementById('chat-input');
            const messageText = messageInput.value.trim();
            
            if (!messageText || !currentRoomId) return;
            
            // TODO: Send to your PHP API
            console.log('Sending message:', { roomId: currentRoomId, message: messageText });
            
            // Add message to chat (in real app, this would come from server)
            const newMessage = {
                id: sampleMessages.length + 1,
                author: 'You',
                avatar: 'Y',
                text: messageText,
                time: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
            };
            
            sampleMessages.push(newMessage);
            loadMessages();
            
            // Clear input
            messageInput.value = '';
            messageInput.style.height = 'auto';
        }

        // Handle search
        function handleSearch(e) {
            const searchTerm = e.target.value.toLowerCase();
            const roomItems = document.querySelectorAll('.room-item');
            
            roomItems.forEach(item => {
                const roomName = item.querySelector('.room-name').textContent.toLowerCase();
                const roomPreview = item.querySelector('.room-preview').textContent.toLowerCase();
                
                if (roomName.includes(searchTerm) || roomPreview.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Show new group modal
        function showNewGroupModal() {
            document.getElementById('new-group-modal').style.display = 'flex';
            document.getElementById('group-name').focus();
        }

        // Close new group modal
        function closeNewGroupModal() {
            document.getElementById('new-group-modal').style.display = 'none';
            document.getElementById('create-group-form').reset();
        }

        // Close add member modal
        function closeAddMemberModal() {
    document.getElementById('add-member-modal').style.display = 'none';
}

        // Show account options
        function showAccountOptions() {
            alert('Account options: Profile, Settings, Logout, etc.');
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

// Initialize the application
document.addEventListener('DOMContentLoaded', init);
    </script>
</body>
</html>