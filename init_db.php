<?php
/**
 * Database Initialization Script
 * Run this once to create tables and insert sample data
 */

require_once 'config.php';

try {
    echo "Initializing database...\n";
    
    // Create tables
    createDatabaseTables();
    
    // Insert sample data
    $db = Database::getInstance()->getConnection();
    
    // Check if sample data already exists
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        echo "Inserting sample data...\n";
        
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

        echo "Sample data inserted successfully!\n";
    } else {
        echo "Sample data already exists.\n";
    }
    
    echo "Database initialization completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>