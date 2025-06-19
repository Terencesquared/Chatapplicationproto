<?php
/**
 * Database Initialization Script
 * Run this once to set up your database tables
 */
require_once 'config.php';

try {
    echo "Starting database initialization...\n";
    initializeDatabase();
    echo "Database initialization completed successfully!\n";
    
    // Create default chat rooms
    echo "Creating default chat rooms...\n";
    $db = Database::getInstance()->getConnection();
    
    // Check if admin user exists, if not create one
    $stmt = $db->prepare("SELECT id FROM users WHERE username = 'admin'");
    $stmt->execute();
    $adminUser = $stmt->fetch();
    
    if (!$adminUser) {
        echo "Creating admin user...\n";
        
        $adminUsername = 'admin';
        $adminEmail = 'admin@chatapp.com';
        $adminPassword = hashPassword('admin123');
        $adminFullName = 'System Administrator';
        
        $stmt = $db->prepare("
            INSERT INTO users (username, email, password_hash, full_name) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([$adminUsername, $adminEmail, $adminPassword, $adminFullName]);
        $adminId = $db->lastInsertId();
        echo "Admin user created: username='admin', password='admin123'\n";
    } else {
        $adminId = $adminUser['id'];
        echo "Admin user already exists.\n";
    }
    
    // Create default chat rooms if they don't exist
    $defaultRooms = [
        ['name' => 'General', 'description' => 'General discussion for everyone', 'is_private' => 0],
        ['name' => 'Tech Talk', 'description' => 'Discuss technology and programming', 'is_private' => 0],
        ['name' => 'Random', 'description' => 'Random conversations and fun', 'is_private' => 0]
    ];
    
    foreach ($defaultRooms as $room) {
        $stmt = $db->prepare("SELECT id FROM chat_rooms WHERE name = ?");
        $stmt->execute([$room['name']]);
        
        if (!$stmt->fetch()) {
            $stmt = $db->prepare("
                INSERT INTO chat_rooms (name, description, is_private, created_by) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$room['name'], $room['description'], $room['is_private'], $adminId]);
            $roomId = $db->lastInsertId();
            
            // Add admin as room participant
            $stmt = $db->prepare("
                INSERT INTO room_participants (room_id, user_id, role) 
                VALUES (?, ?, 'admin')
            ");
            $stmt->execute([$roomId, $adminId]);
            
            echo "Created room: {$room['name']}\n";
        } else {
            echo "Room '{$room['name']}' already exists.\n";
        }
    }
    
    // Create sample users if none exist (except admin)
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username != 'admin'");
    $stmt->execute();
    $userCount = $stmt->fetchColumn();
    
    if ($userCount == 0) {
        echo "Creating sample users...\n";
        
        $sampleUsers = [
            ['john_doe', 'John Doe', 'john@example.com'],
            ['jane_smith', 'Jane Smith', 'jane@example.com'],
            ['mike_wilson', 'Mike Wilson', 'mike@example.com'],
            ['sarah_jones', 'Sarah Jones', 'sarah@example.com']
        ];
        
        foreach ($sampleUsers as $user) {
            $password = hashPassword('password123');
            $stmt = $db->prepare("
                INSERT INTO users (username, full_name, email, password_hash) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$user[0], $user[1], $user[2], $password]);
            echo "Created user: {$user[0]} (password: password123)\n";
        }
    }
    
    // Add welcome messages to General room
    $stmt = $db->prepare("SELECT id FROM chat_rooms WHERE name = 'General'");
    $stmt->execute();
    $generalRoom = $stmt->fetch();
    
    if ($generalRoom) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE room_id = ?");
        $stmt->execute([$generalRoom['id']]);
        $messageCount = $stmt->fetchColumn();
        
        if ($messageCount == 0) {
            echo "Adding welcome message to General room...\n";
            $stmt = $db->prepare("
                INSERT INTO messages (room_id, user_id, message_text, message_type) 
                VALUES (?, ?, ?, 'text')
            ");
            $stmt->execute([
                $generalRoom['id'], 
                $adminId, 
                'Welcome to ChatApp! This is the general discussion room. Feel free to introduce yourself and start chatting!'
            ]);
        }
    }
    
    echo "\nDatabase setup complete!\n";
    echo "======================\n";
    echo "Admin Login Details:\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
    echo "======================\n";
    echo "Sample Users (all have password: password123):\n";
    echo "- john_doe\n";
    echo "- jane_smith\n";
    echo "- mike_wilson\n";
    echo "- sarah_jones\n";
    echo "======================\n";
    echo "You can now delete this file for security.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>